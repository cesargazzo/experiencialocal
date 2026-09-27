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
}
