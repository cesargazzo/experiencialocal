<?php

namespace Tests\Feature;

use App\Models\SecurityEvent;
use App\Models\User;
use App\Support\Totp;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TwoFactorTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function the_totp_codes_match_the_rfc_6238_test_vectors(): void
    {
        // Secreto del RFC: "12345678901234567890" en base32; códigos de 6 dígitos.
        $secret = Totp::base32Encode('12345678901234567890');
        $this->assertSame('GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ', $secret);
        $this->assertSame('12345678901234567890', Totp::base32Decode($secret));

        foreach ([59 => '287082', 1111111109 => '081804', 1234567890 => '005924', 2000000000 => '279037'] as $time => $code) {
            $this->assertSame($code, Totp::code($secret, Totp::currentStep($time)));
            $this->assertNotNull(Totp::verify($secret, $code, null, $time));
        }
        $this->assertNull(Totp::verify($secret, '000000', null, 59));
    }

    #[Test]
    public function a_person_turns_it_on_scanning_the_code_and_gets_recovery_codes(): void
    {
        $user = User::factory()->create(['password' => 'Segura2026x']);

        $this->actingAs($user)->post(route('cuenta.2fa.start'), ['password' => 'Otra2026x'])->assertSessionHasErrorsIn('twoFactor', 'password');
        $this->actingAs($user)->post(route('cuenta.2fa.start'), ['password' => 'Segura2026x']);
        $secret = $user->fresh()->two_factor_secret;
        $this->assertNotNull($secret);
        $this->assertFalse($user->fresh()->hasTwoFactor(), 'Hasta confirmar el primer código no se exige.');

        $this->actingAs($user)->get(route('cuenta.seguridad'))->assertSee('<svg', false)->assertSee(trim(chunk_split($secret, 4, ' ')));
        $this->actingAs($user)->post(route('cuenta.2fa.confirm'), ['code' => '123456'])->assertSessionHasErrorsIn('twoFactor', 'code');
        $this->actingAs($user)->post(route('cuenta.2fa.confirm'), ['code' => Totp::code($secret, Totp::currentStep())])
            ->assertSessionHas('recovery_codes', fn ($codes) => count($codes) === 8);

        $this->assertTrue($user->fresh()->hasTwoFactor());
        $this->assertNotSame($secret, \DB::table('users')->where('id', $user->id)->value('two_factor_secret'), 'El secreto se guarda cifrado.');
        $this->assertTrue(SecurityEvent::where('type', '2fa.enabled')->exists());
    }

    #[Test]
    public function logging_in_asks_for_the_code_and_a_code_cannot_be_reused(): void
    {
        $this->travelTo(Carbon::parse('2026-10-01 12:00:00'));
        $secret = Totp::generateSecret();
        $user = User::factory()->create(['password' => 'Segura2026x', 'two_factor_secret' => $secret, 'two_factor_confirmed_at' => now()]);

        $this->post(route('login'), ['email' => $user->email, 'password' => 'Segura2026x'])->assertRedirect(route('login.2fa'));
        $this->assertGuest();

        $this->post(route('login.2fa'), ['code' => '000000'])->assertSessionHasErrors('code');
        $this->assertGuest();

        $code = Totp::code($secret, Totp::currentStep());
        $this->post(route('login.2fa'), ['code' => $code])->assertRedirect(route('home'));
        $this->assertAuthenticatedAs($user);

        auth()->logout();
        $this->post(route('login'), ['email' => $user->email, 'password' => 'Segura2026x']);
        $this->post(route('login.2fa'), ['code' => $code])->assertSessionHasErrors('code');
        $this->assertGuest();
    }

    #[Test]
    public function a_recovery_code_works_once(): void
    {
        $user = User::factory()->create(['password' => 'Segura2026x', 'two_factor_secret' => Totp::generateSecret(), 'two_factor_confirmed_at' => now()]);
        $codes = $user->regenerateRecoveryCodes();

        $this->post(route('login'), ['email' => $user->email, 'password' => 'Segura2026x']);
        $this->post(route('login.2fa'), ['code' => strtolower($codes[0])])->assertRedirect(route('home'));
        $this->assertAuthenticatedAs($user);
        $this->assertCount(7, $user->fresh()->two_factor_recovery_codes);

        auth()->logout();
        $this->post(route('login'), ['email' => $user->email, 'password' => 'Segura2026x']);
        $this->post(route('login.2fa'), ['code' => $codes[0]])->assertSessionHasErrors('code');
        $this->assertGuest();
    }

    #[Test]
    public function the_administration_requires_two_factor(): void
    {
        $admin = User::factory()->admin()->create(['two_factor_secret' => null, 'two_factor_confirmed_at' => null]);

        $this->actingAs($admin)->get(route('admin.usuarios'))->assertRedirect(route('cuenta.seguridad').'#doble-factor');
        $this->actingAs(User::factory()->admin()->create())->get(route('admin.usuarios'))->assertOk();
    }

    #[Test]
    public function turning_it_off_needs_the_password_and_a_valid_code(): void
    {
        $secret = Totp::generateSecret();
        $user = User::factory()->create(['password' => 'Segura2026x', 'two_factor_secret' => $secret, 'two_factor_confirmed_at' => now()]);

        $this->actingAs($user)->delete(route('cuenta.2fa.destroy'), ['password' => 'Segura2026x', 'code' => '000000'])->assertSessionHasErrorsIn('twoFactor', 'code');
        $this->assertTrue($user->fresh()->hasTwoFactor());

        $this->actingAs($user)->delete(route('cuenta.2fa.destroy'), ['password' => 'Segura2026x', 'code' => Totp::code($secret, Totp::currentStep())]);
        $this->assertFalse($user->fresh()->hasTwoFactor());
    }

    #[Test]
    public function turned_off_for_the_platform_it_is_not_asked_and_the_setup_is_kept(): void
    {
        config(['tinku.two_factor' => false]);
        $secret = Totp::generateSecret();
        $user = User::factory()->create(['email' => 'ana@tinku.test', 'password' => 'Segura2026x', 'two_factor_secret' => $secret, 'two_factor_confirmed_at' => now()]);

        $this->post(route('login'), ['email' => 'ana@tinku.test', 'password' => 'Segura2026x'])->assertRedirect(route('home'));
        $this->assertAuthenticatedAs($user);

        $admin = User::factory()->admin()->create(['two_factor_secret' => null, 'two_factor_confirmed_at' => null]);
        $this->actingAs($admin)->get(route('admin.usuarios'))->assertOk()->assertSee('El doble factor está apagado');
        $this->actingAs($admin)->get(route('cuenta.seguridad'))->assertOk()->assertDontSee('id="doble-factor"', false);
        $this->actingAs($admin)->post(route('cuenta.2fa.start'), ['password' => 'password'])->assertNotFound();

        config(['tinku.two_factor' => true]);
        $this->assertTrue($user->fresh()->hasTwoFactor());
    }

    #[Test]
    public function wrong_codes_lock_the_person_for_fifteen_minutes_even_with_the_right_code(): void
    {
        $this->travelTo(Carbon::parse('2026-10-01 12:00:00'));
        $secret = Totp::generateSecret();
        $user = User::factory()->create(['password' => 'Segura2026x', 'two_factor_secret' => $secret, 'two_factor_confirmed_at' => now()]);

        $this->post(route('login'), ['email' => $user->email, 'password' => 'Segura2026x']);
        foreach (range(1, 5) as $attempt) {
            $this->post(route('login.2fa'), ['code' => '000000']);
        }
        $this->post(route('login.2fa'), ['code' => Totp::code($secret, Totp::currentStep())])->assertSessionHasErrors('code');
        $this->assertGuest();
        $this->assertTrue(SecurityEvent::where('type', 'login.locked')->exists());

        $this->travel(16)->minutes();
        $this->post(route('login'), ['email' => $user->email, 'password' => 'Segura2026x']);
        $this->post(route('login.2fa'), ['code' => Totp::code($secret, Totp::currentStep())])->assertRedirect(route('home'));
        $this->assertAuthenticatedAs($user);
    }

    #[Test]
    public function an_admin_session_without_a_code_is_asked_for_it_before_entering(): void
    {
        $secret = Totp::generateSecret();
        $admin = User::factory()->admin()->create(['two_factor_secret' => $secret]);

        // Sesión abierta sin haber ingresado el código (por ejemplo, con "recordarme").
        $this->be($admin);
        $this->get(route('admin.usuarios'))->assertRedirect(route('cuenta.2fa.verify'));
        $this->get(route('cuenta.2fa.verify'))->assertOk();
        $this->post(route('cuenta.2fa.verify'), ['code' => '000000'])->assertSessionHasErrors('code');
        $this->post(route('cuenta.2fa.verify'), ['code' => Totp::code($secret, Totp::currentStep())])->assertRedirect(route('admin.usuarios'));
        $this->get(route('admin.usuarios'))->assertOk();
    }

    #[Test]
    public function an_active_second_factor_cannot_be_replaced_without_turning_it_off_with_a_code(): void
    {
        $secret = Totp::generateSecret();
        $user = User::factory()->create(['password' => 'Segura2026x', 'two_factor_secret' => $secret, 'two_factor_confirmed_at' => now()]);

        $this->actingAs($user)->post(route('cuenta.2fa.start'), ['password' => 'Segura2026x'])->assertSessionHasErrorsIn('twoFactor', 'password');
        $this->assertSame($secret, $user->fresh()->two_factor_secret);
        $this->assertTrue($user->fresh()->hasTwoFactor());
    }

    #[Test]
    public function every_page_is_sent_with_security_headers(): void
    {
        $this->get(route('login'))
            ->assertHeader('X-Frame-Options', 'DENY')
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
    }
}
