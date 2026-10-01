<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\Totp;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MakeAdminCommandTest extends TestCase
{
    use RefreshDatabase;

    private function temporaryPasswordFrom(string $output): string
    {
        // La contraseña puede empezar con un punto: se toma lo que sigue al espacio después del relleno.
        preg_match('/Contraseña de única vez\s+\.+\s+(\S+)/u', $output, $match);

        return $match[1] ?? '';
    }

    #[Test]
    public function it_creates_an_admin_with_a_one_time_password_that_must_be_changed(): void
    {
        $this->assertSame(0, Artisan::call('tinku:admin', ['email' => 'Equipo@Tinku.com', '--name' => 'Equipo Tinku']));
        $temporary = $this->temporaryPasswordFrom(Artisan::output());

        $user = User::where('email', 'equipo@tinku.com')->firstOrFail();
        $this->assertTrue($user->isAdmin());
        $this->assertTrue($user->must_change_password);
        $this->assertTrue($user->password_expires_at->between(now()->addHours(23), now()->addHours(25)));
        $this->assertGreaterThanOrEqual(16, strlen($temporary));

        $this->post(route('login'), ['email' => 'equipo@tinku.com', 'password' => $temporary])->assertRedirect();
        $this->assertAuthenticatedAs($user);

        // Hasta cambiarla, cualquier página lleva al cambio de contraseña.
        $this->get(route('admin.verificaciones'))->assertRedirect(route('cuenta.seguridad'));
        $this->get(route('home'))->assertRedirect(route('cuenta.seguridad'));

        $this->put(route('cuenta.seguridad.update'), [
            'current_password' => $temporary, 'password' => $temporary, 'password_confirmation' => $temporary,
        ])->assertSessionHasErrors('password');

        $this->put(route('cuenta.seguridad.update'), [
            'current_password' => $temporary, 'password' => 'NuevaClave2026', 'password_confirmation' => 'NuevaClave2026',
        ])->assertSessionHasNoErrors();

        $user->refresh();
        $this->assertFalse($user->must_change_password);
        $this->assertNull($user->password_expires_at);

        // Después, la administración pide activar el doble factor.
        $this->get(route('admin.verificaciones'))->assertRedirect(route('cuenta.seguridad').'#doble-factor');
        $this->post(route('cuenta.2fa.start'), ['password' => 'NuevaClave2026']);
        $secret = $user->fresh()->two_factor_secret;
        $this->post(route('cuenta.2fa.confirm'), ['code' => Totp::code($secret, Totp::currentStep())]);
        $this->get(route('admin.verificaciones'))->assertOk();
    }

    #[Test]
    public function it_promotes_an_existing_account_and_can_revoke_it(): void
    {
        $user = User::factory()->create(['email' => 'marta@example.com']);

        $this->artisan('tinku:admin', ['email' => 'marta@example.com'])->assertSuccessful();
        $this->assertTrue($user->fresh()->isAdmin());

        $this->artisan('tinku:admin', ['email' => 'marta@example.com', '--revoke' => true])->assertSuccessful();
        $this->assertFalse($user->fresh()->isTeamMember());
    }

    #[Test]
    public function an_expired_one_time_password_cannot_be_used(): void
    {
        $user = User::factory()->create();
        $temporary = $user->issueTemporaryPassword();
        $this->travel(25)->hours();

        $this->post(route('login'), ['email' => $user->email, 'password' => $temporary])
            ->assertSessionHasErrors(['email' => 'La contraseña de única vez venció. Pedí una nueva.']);
        $this->assertGuest();
    }
}
