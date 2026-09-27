<?php

namespace Tests\Feature;

use App\Enums\HostStatus;
use App\Enums\VerificationLevel;
use App\Enums\VerificationType;
use App\Models\HostProfile;
use App\Models\Province;
use App\Models\SecurityEvent;
use App\Models\User;
use App\Services\VerificationService;
use Database\Seeders\CategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AdminUsersTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CategorySeeder::class);
    }

    #[Test]
    public function admins_list_and_search_all_accounts(): void
    {
        $admin = User::factory()->admin()->create();
        User::factory()->create(['name' => 'Rosa Díaz', 'email' => 'rosa@example.com']);
        User::factory()->create(['name' => 'Pedro Gómez']);

        $this->actingAs($admin)->get(route('admin.usuarios'))->assertOk()->assertSee('Rosa Díaz')->assertSee('Pedro Gómez');
        $this->actingAs($admin)->get(route('admin.usuarios', ['q' => 'rosa']))->assertSee('Rosa Díaz')->assertDontSee('Pedro Gómez');
        $this->actingAs(User::factory()->create())->get(route('admin.usuarios'))->assertForbidden();
    }

    #[Test]
    public function several_accounts_can_be_validated_at_once_with_a_reason(): void
    {
        $admin = User::factory()->admin()->create();
        $first = User::factory()->level(VerificationLevel::None)->create();
        $second = User::factory()->level(VerificationLevel::Contact)->create();

        $this->actingAs($admin)->post(route('admin.usuarios.validar'), [
            'users' => [$first->id, $second->id], 'level' => '2', 'reason' => 'Validados en persona en la feria',
        ])->assertSessionHas('status', 'Validamos 2 cuentas.');

        foreach ([$first, $second] as $user) {
            $user->refresh();
            $this->assertSame(VerificationLevel::Document, $user->verification_level);
            $this->assertTrue($user->verifications()->where('provider', 'manual')->where('reviewed_by', $admin->id)->exists());
            $this->assertNotNull($user->email_verified_at);
        }
        $this->assertSame(2, SecurityEvent::where('type', 'user.validated_manually')->count());

        $this->actingAs($admin)->post(route('admin.usuarios.validar'), ['users' => [$first->id], 'level' => '3'])->assertSessionHasErrors('reason');
    }

    #[Test]
    public function validating_to_level_three_activates_a_host_in_review(): void
    {
        $admin = User::factory()->admin()->create();
        $host = User::factory()->level(VerificationLevel::Document)->create();
        $profile = HostProfile::factory()->inReview()->for($host)->create();

        $this->actingAs($admin)->post(route('admin.usuarios.validar'), ['users' => [$host->id], 'level' => '3', 'reason' => 'Visité el domicilio']);

        $this->assertSame(VerificationLevel::Residence, $host->fresh()->verification_level);
        $this->assertSame(HostStatus::Active, $profile->fresh()->status);
    }

    #[Test]
    public function a_suspended_account_loses_its_session_and_cannot_log_in(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create(['password' => 'Correcta2026']);

        $this->actingAs($admin)->get(route('admin.usuarios.show', $user))->assertOk()->assertSee($user->email);
        $this->actingAs($admin)->post(route('admin.usuarios.suspension', $user), ['reason' => 'Denuncias de otros usuarios'])->assertSessionHasNoErrors();
        $this->assertTrue($user->fresh()->isSuspended());

        $this->actingAs($user->fresh())->get(route('cuenta.perfil'))->assertRedirect(route('login'));
        $this->assertGuest();

        $this->post(route('login'), ['email' => $user->email, 'password' => 'Correcta2026'])
            ->assertSessionHasErrors(['email' => 'Tu cuenta está suspendida. Escribinos para revisarla.']);
        $this->assertGuest();

        $this->actingAs($admin)->post(route('admin.usuarios.suspension', $user));
        $this->assertFalse($user->fresh()->isSuspended());
    }

    #[Test]
    public function an_admin_cannot_suspend_themselves(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->post(route('admin.usuarios.suspension', $admin), ['reason' => 'prueba'])->assertStatus(422);
        $this->assertFalse($admin->fresh()->isSuspended());
    }

    #[Test]
    public function the_sms_switch_changes_level_one_for_every_account(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->level(VerificationLevel::None)->create();
        $user->verifications()->create(['type' => 'email', 'provider' => 'internal', 'status' => 'approved']);
        app(VerificationService::class)->recalculateLevel($user);
        $this->assertSame(VerificationLevel::Contact, $user->fresh()->verification_level);

        $this->actingAs($admin)->get(route('admin.configuracion'))->assertOk()->assertSee('cuenta baja al nivel 0');
        $this->actingAs($admin)->put(route('admin.configuracion.update'), ['sms_verification' => '1'])->assertSessionHas('status');
        $this->assertSame(VerificationLevel::None, $user->fresh()->verification_level);
        $this->assertTrue(SecurityEvent::where('type', 'settings.updated')->exists());

        $this->actingAs($admin)->put(route('admin.configuracion.update'), []);
        $this->assertSame(VerificationLevel::Contact, $user->fresh()->verification_level);
    }

    #[Test]
    public function logging_in_records_the_last_login_even_when_coming_back_later(): void
    {
        $user = User::factory()->create(['password' => 'Correcta2026']);

        $this->post(route('login'), ['email' => $user->email, 'password' => 'Correcta2026'])->assertRedirect();
        $this->assertNotNull($user->fresh()->last_login_at);
        $this->assertNotNull($user->fresh()->last_seen_at);

        $this->travel(10)->minutes();
        $this->get(route('home'))->assertOk();
        $this->assertTrue($user->fresh()->last_seen_at->gt($user->fresh()->last_login_at));
    }

    #[Test]
    public function admins_filter_by_signup_date_province_and_document_number(): void
    {
        $this->travelTo(Carbon::parse('2026-10-01 12:00:00'));
        $admin = User::factory()->admin()->create(['name' => 'Equipo Tinku', 'created_at' => now()->subYear()]);
        $laRioja = Province::where('code', 'AR-F')->value('id');
        $recent = User::factory()->create(['name' => 'Recién Llegada', 'province_id' => $laRioja, 'city' => 'Chilecito', 'created_at' => now()->subDays(2)]);
        $old = User::factory()->create(['name' => 'Cuenta Vieja', 'created_at' => now()->subMonths(3), 'last_login_at' => now()->subHour()]);
        app(VerificationService::class)->submit($old, VerificationType::Document, ['document_country' => 'AR', 'document_type' => 'dni', 'document_number' => '30.111.222']);

        $this->actingAs($admin)->get(route('admin.usuarios', ['alta' => 'semana']))
            ->assertOk()->assertSee('Recién Llegada')->assertDontSee('Cuenta Vieja');

        $this->actingAs($admin)->get(route('admin.usuarios', ['provincia' => $laRioja]))
            ->assertSee('Recién Llegada')->assertSee('Chilecito, La Rioja')->assertDontSee('Cuenta Vieja');

        $this->actingAs($admin)->get(route('admin.usuarios', ['dni' => '30111222']))
            ->assertSee('Cuenta Vieja')->assertDontSee('Recién Llegada');

        $this->actingAs($admin)->get(route('admin.usuarios', ['orden' => 'ingreso']))
            ->assertSeeInOrder(['Cuenta Vieja', 'Recién Llegada']);
        $this->actingAs($admin)->get(route('admin.usuarios'))
            ->assertSeeInOrder(['Recién Llegada', 'Cuenta Vieja', 'Equipo Tinku']);
    }
}
