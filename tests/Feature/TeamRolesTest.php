<?php

namespace Tests\Feature;

use App\Enums\TeamRole;
use App\Enums\VerificationLevel;
use App\Models\Experience;
use App\Models\SecurityEvent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TeamRolesTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function each_role_only_opens_its_own_sections(): void
    {
        $someone = User::factory()->create();
        $sections = [
            'usuarios' => route('admin.usuarios'),
            'ficha' => route('admin.usuarios.show', $someone),
            'verificaciones' => route('admin.verificaciones'),
            'experiencias' => route('admin.experiencias'),
            'denuncias' => route('admin.denuncias'),
            'seguridad' => route('admin.seguridad'),
            'auditoria' => route('admin.auditoria'),
            'configuracion' => route('admin.configuracion'),
            'equipo' => route('admin.equipo'),
        ];
        $allowed = [
            TeamRole::Admin->value => array_keys($sections),
            TeamRole::Verifier->value => ['usuarios', 'ficha', 'verificaciones'],
            TeamRole::Moderator->value => ['experiencias', 'denuncias'],
            TeamRole::Support->value => ['usuarios', 'ficha'],
            TeamRole::Auditor->value => ['seguridad', 'auditoria'],
        ];

        foreach (TeamRole::cases() as $role) {
            $member = User::factory()->team($role)->create();
            foreach ($sections as $name => $url) {
                $status = $this->actingAs($member)->get($url)->status();
                $this->assertSame(in_array($name, $allowed[$role->value], true) ? 200 : 403, $status, "{$role->label()} en {$name}");
            }
        }

        $this->actingAs($someone)->get(route('admin.inicio'))->assertForbidden();
    }

    #[Test]
    public function the_admin_entrance_and_bar_show_only_what_the_role_can_open(): void
    {
        $moderator = User::factory()->team(TeamRole::Moderator)->create();

        $this->actingAs($moderator)->get(route('admin.inicio'))->assertRedirect(route('admin.experiencias'));
        $this->actingAs($moderator)->get(route('admin.experiencias'))
            ->assertSee(route('admin.denuncias'), false)
            ->assertDontSee(route('admin.usuarios'), false)
            ->assertDontSee(route('admin.seguridad'), false);
    }

    #[Test]
    public function a_moderator_does_not_see_the_hosts_full_name_or_email(): void
    {
        $host = User::factory()->create(['first_name' => 'Marta', 'last_name' => 'Quiroga Ledesma', 'email' => 'marta.privada@example.com']);
        $experience = Experience::factory()->create();
        $experience->host->update(['user_id' => $host->id, 'display_name' => 'Marta Quiroga Ledesma']);

        $this->actingAs(User::factory()->team(TeamRole::Moderator)->create())->get(route('admin.experiencias.show', $experience))
            ->assertOk()->assertSee('Marta')->assertDontSee('Quiroga')->assertDontSee('marta.privada@example.com');
    }

    #[Test]
    public function only_full_admins_assign_roles_and_with_safeguards(): void
    {
        $admin = User::factory()->admin()->create();
        $verified = User::factory()->level(VerificationLevel::Document)->create();
        $unverified = User::factory()->level(VerificationLevel::Contact)->create();

        $this->actingAs(User::factory()->team(TeamRole::Verifier)->create())
            ->put(route('admin.usuarios.rol', $verified), ['team_role' => 'admin'])->assertForbidden();

        $this->actingAs($admin)->put(route('admin.usuarios.rol', $unverified), ['team_role' => 'support'])->assertSessionHasErrors('team_role');
        $this->assertNull($unverified->fresh()->team_role);

        $this->actingAs($admin)->put(route('admin.usuarios.rol', $verified), ['team_role' => 'moderator'])->assertSessionHasNoErrors();
        $this->assertSame(TeamRole::Moderator, $verified->fresh()->team_role);
        $this->assertTrue(SecurityEvent::where('type', 'team.role_changed')->where('user_id', $verified->id)->exists());

        $this->actingAs($admin)->put(route('admin.usuarios.rol', $admin), ['team_role' => ''])->assertSessionHasErrors('team_role');
        $this->assertTrue($admin->fresh()->isAdmin());

        $other = User::factory()->admin()->create();
        $this->actingAs($admin)->put(route('admin.usuarios.rol', $other), ['team_role' => ''])->assertSessionHasNoErrors();
        $this->assertFalse($other->fresh()->isTeamMember());
    }
}
