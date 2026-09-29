<?php

namespace Tests\Feature;

use App\Enums\HostStatus;
use App\Enums\VerificationLevel;
use App\Enums\VerificationType;
use App\Exceptions\VerificationException;
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
        $withoutDocument = User::factory()->level(VerificationLevel::Contact)->create(['name' => 'Sin Documento']);
        // Los dos primeros ya cargaron su documento; en lote se valida ese.
        app(VerificationService::class)->submit($first, VerificationType::Document, ['document_country' => 'AR', 'document_number' => '20111222']);
        app(VerificationService::class)->submit($second, VerificationType::Document, ['document_country' => 'AR', 'document_number' => '20333444']);

        $this->actingAs($admin)->post(route('admin.usuarios.validar'), [
            'users' => [$first->id, $second->id, $withoutDocument->id], 'level' => '2', 'reason' => 'Validados en persona en la feria',
        ])->assertSessionHas('status', 'Validamos 2 cuentas.')
            ->assertSessionHasErrors(['users' => 'Sin Documento: Para validar el documento a mano cargá el país y el número del documento.']);
        $this->assertSame(VerificationLevel::Contact, $withoutDocument->fresh()->verification_level);

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

        $this->actingAs($admin)->post(route('admin.usuarios.validar'), ['users' => [$host->id], 'level' => '3', 'reason' => 'Visité el domicilio', 'document_country' => 'AR', 'document_number' => '28999111']);

        $this->assertSame(VerificationLevel::Residence, $host->fresh()->verification_level);
        $this->assertSame(HostStatus::Active, $profile->fresh()->status);
    }

    #[Test]
    public function a_suspended_account_loses_its_session_and_cannot_log_in(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create(['password' => 'Correcta2026']);

        $this->actingAs($admin)->get(route('admin.usuarios.show', $user))->assertOk()->assertSee($user->email);
        $this->assertTrue(SecurityEvent::where('type', 'admin.user_viewed')->where('user_id', $admin->id)->where('metadata->account_id', $user->id)->exists(), 'Ver datos personales queda registrado.');
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

    #[Test]
    public function a_document_already_in_another_account_cannot_be_validated_by_hand(): void
    {
        $admin = User::factory()->admin()->create();
        $owner = User::factory()->level(VerificationLevel::Contact)->create(['name' => 'Titular Real']);
        app(VerificationService::class)->submit($owner, VerificationType::Document, ['document_country' => 'AR', 'document_number' => '30.111.222']);
        $impostor = User::factory()->level(VerificationLevel::Contact)->create();

        $this->actingAs($admin)->post(route('admin.usuarios.validar'), [
            'users' => [$impostor->id], 'level' => '2', 'reason' => 'Validado en persona', 'document_country' => 'AR', 'document_number' => '30111222',
        ])->assertSessionHasErrors('users');

        $this->assertStringContainsString('Titular Real', session('errors')->first('users'));
        $this->assertSame(VerificationLevel::Contact, $impostor->fresh()->verification_level);
        $this->assertSame(1, SecurityEvent::where('type', 'user.validation_blocked')->count());

        // Con un documento nuevo sí; y después nadie más puede usarlo.
        $this->actingAs($admin)->post(route('admin.usuarios.validar'), [
            'users' => [$impostor->id], 'level' => '2', 'reason' => 'Validado en persona', 'document_country' => 'AR', 'document_number' => '40555666',
        ])->assertSessionHasNoErrors();
        $this->assertSame(VerificationLevel::Document, $impostor->fresh()->verification_level);
        $this->expectException(VerificationException::class);
        app(VerificationService::class)->submit(User::factory()->create(), VerificationType::Document, ['document_country' => 'AR', 'document_number' => '40555666']);
    }

    #[Test]
    public function accounts_sharing_a_document_are_listed_and_a_verification_can_be_revoked(): void
    {
        $admin = User::factory()->admin()->create();
        $first = User::factory()->create(['name' => 'Primera Cuenta']);
        $second = User::factory()->create(['name' => 'Segunda Cuenta']);
        $hash = VerificationService::documentHash('AR', '30111222');
        foreach ([$first, $second] as $user) {
            $user->verifications()->create(['type' => VerificationType::Document, 'provider' => 'manual', 'status' => 'approved', 'document_country' => 'AR', 'document_hash' => $hash, 'submitted_at' => now(), 'reviewed_at' => now()]);
        }

        $this->actingAs($admin)->get(route('admin.usuarios'))->assertSee('Documentos en más de una cuenta')->assertSeeInOrder(['Primera Cuenta', 'Segunda Cuenta']);

        $verification = $second->verifications()->where('type', 'document')->firstOrFail();
        $this->actingAs($admin)->post(route('admin.usuarios.verificaciones.revocar', [$second, $verification]), ['reason' => 'Documento de otra persona'])
            ->assertSessionHasNoErrors();

        $this->assertSame('rejected', $verification->fresh()->status->value);
        $this->assertSame(1, $second->notifications()->count());
        $this->assertSame(1, SecurityEvent::where('type', 'verification.revoked')->count());
        $this->actingAs($admin)->get(route('admin.usuarios'))->assertDontSee('Documentos en más de una cuenta');
    }

    #[Test]
    public function the_person_gets_a_notice_when_their_account_is_validated(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->level(VerificationLevel::Contact)->create();

        $this->actingAs($admin)->post(route('admin.usuarios.validar'), [
            'users' => [$user->id], 'level' => '3', 'reason' => 'Validado en persona', 'document_country' => 'AR', 'document_number' => '27123456',
        ])->assertSessionHasNoErrors();

        $notice = $user->notifications()->sole();
        $this->assertSame('Tu cuenta está validada: Identidad y domicilio validados', $notice->data['title']);
        $this->actingAs($user)->get(route('home'))->assertSee('Avisos: 1 sin leer');
    }

    #[Test]
    public function the_kpis_count_accounts_and_the_login_one_filters_the_last_five_days(): void
    {
        $this->travelTo(Carbon::parse('2026-10-01 12:00:00'));
        $admin = User::factory()->admin()->create(['name' => 'Equipo Tinku', 'last_login_at' => now()->subDays(30), 'last_seen_at' => null]);
        User::factory()->create(['name' => 'Entró Ayer', 'last_login_at' => now()->subDay()]);
        User::factory()->create(['name' => 'Recordada Activa', 'last_login_at' => now()->subDays(20), 'last_seen_at' => now()->subDays(2)]);
        User::factory()->create(['name' => 'Hace Mucho', 'last_login_at' => now()->subDays(9), 'last_seen_at' => now()->subDays(9)]);

        $response = $this->actingAs($admin)->get(route('admin.usuarios'))->assertOk();
        $kpis = collect($response->viewData('kpis'))->keyBy('label');
        $this->assertSame(4, $kpis['Cuentas']['value']);
        // Entró Ayer, Recordada Activa (con "recordarme") y quien está mirando la página.
        $this->assertSame(3, $kpis['Ingresaron en los últimos 5 días']['value']);
        $response->assertSee(route('admin.usuarios', ['ingreso' => '5dias']), false);

        $this->actingAs($admin)->get(route('admin.usuarios', ['ingreso' => '5dias']))
            ->assertOk()->assertSeeInOrder(['Entró Ayer', 'Recordada Activa'])->assertDontSee('Hace Mucho')
            ->assertViewHas('kpis', fn (array $kpis) => collect($kpis)->firstWhere('label', 'Ingresaron en los últimos 5 días')['active']);
    }
}
