<?php

namespace Tests\Feature;

use App\Enums\HostStatus;
use App\Enums\VerificationLevel;
use App\Models\HostProfile;
use App\Models\SecurityEvent;
use App\Models\User;
use Database\Seeders\CategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
}
