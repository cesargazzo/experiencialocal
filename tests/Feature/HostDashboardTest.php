<?php

namespace Tests\Feature;

use App\Livewire\HostOnboarding;
use App\Models\Experience;
use App\Models\HostProfile;
use App\Models\Plan;
use App\Models\User;
use Database\Seeders\PlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class HostDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PlanSeeder::class);
    }

    #[Test]
    public function a_host_sees_their_experiences_even_while_they_wait_for_review(): void
    {
        $profile = HostProfile::factory()->inReview()->create(['plan_id' => Plan::where('slug', 'free')->value('id')]);
        Experience::factory()->inReview()->for($profile, 'host')->create(['title' => 'Cocina criolla moderna']);
        Experience::factory()->for($profile, 'host')->create(['title' => 'Rechazada', 'status' => 'draft', 'published_at' => null, 'approved_at' => null, 'rejection_reason' => 'La foto es de stock.']);

        $this->actingAs($profile->user)->get(route('home'))->assertSee(route('anfitrion.panel'), false)->assertSee('Mi espacio de anfitrión');
        $this->actingAs($profile->user)->get(route('anfitrion.panel'))
            ->assertOk()
            ->assertSee('Cocina criolla moderna')
            ->assertSee('En revisión')
            ->assertSee('La estamos revisando')
            ->assertSee('Motivo: La foto es de stock.')
            ->assertSee('Tu plan no permite más experiencias activas')
            ->assertDontSee('Creá otra experiencia');
    }

    #[Test]
    public function a_host_with_room_in_their_plan_can_create_another_starting_at_the_experience_step(): void
    {
        $profile = HostProfile::factory()->create(['plan_id' => Plan::whereNull('max_experiences')->value('id')]);

        $this->actingAs($profile->user)->get(route('anfitrion.panel'))->assertOk()->assertSee('Creá otra experiencia');
        Livewire::actingAs($profile->user)->test(HostOnboarding::class)->assertSet('step', 2)->assertSet('address', $profile->address);
    }

    #[Test]
    public function someone_who_is_not_a_host_yet_goes_to_the_signup(): void
    {
        $this->actingAs(User::factory()->create())->get(route('anfitrion.panel'))->assertRedirect(route('anfitrion.registro'));
    }

    #[Test]
    public function the_owner_sees_an_edit_bar_on_their_experience_page_and_visitors_do_not(): void
    {
        $experience = Experience::factory()->inReview()->create();

        $this->actingAs($experience->host->user)->get(route('experiencias.show', $experience))
            ->assertOk()
            ->assertSee('La estamos revisando. Mientras tanto la podés editar.')
            ->assertSee(route('anfitrion.experiencias.editar', $experience), false);

        $published = Experience::factory()->create();
        $this->actingAs(User::factory()->create())->get(route('experiencias.show', $published))
            ->assertOk()
            ->assertDontSee('owner-bar', false);
    }

    #[Test]
    public function the_home_page_tells_the_host_why_their_experience_is_not_listed(): void
    {
        $experience = Experience::factory()->inReview()->create(['title' => 'Caminata por el río Amarillo']);

        $this->actingAs($experience->host->user)->get(route('home'))
            ->assertOk()
            ->assertSee('Tu experiencia')
            ->assertSee('Caminata por el río Amarillo')
            ->assertSee('(en revisión)')
            ->assertSee('solo se listan las publicadas');

        $this->actingAs(User::factory()->create())->get(route('home'))->assertDontSee('Caminata por el río Amarillo');
    }
}
