<?php

namespace Tests\Feature;

use App\Enums\ExperienceStatus;
use App\Models\Experience;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class FavoritesTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function a_person_saves_and_removes_a_favorite_and_sees_it_in_their_account(): void
    {
        $user = User::factory()->create();
        $experience = Experience::factory()->create(['title' => 'Empanadas en el patio']);

        $this->actingAs($user)->postJson(route('favoritas.toggle', $experience))->assertOk()->assertJson(['favorite' => true]);
        $this->actingAs($user)->get(route('cuenta.favoritas'))->assertOk()->assertSee('Empanadas en el patio');
        $this->actingAs($user)->get(route('experiencias.show', $experience))->assertSee('Sacar de favoritas');

        $this->actingAs($user)->postJson(route('favoritas.toggle', $experience))->assertJson(['favorite' => false]);
        $this->actingAs($user)->get(route('cuenta.favoritas'))->assertDontSee('Empanadas en el patio');
    }

    #[Test]
    public function only_published_experiences_can_be_saved_and_guests_are_sent_to_log_in(): void
    {
        $draft = Experience::factory()->create(['status' => ExperienceStatus::Draft]);
        $this->actingAs(User::factory()->create())->postJson(route('favoritas.toggle', $draft))->assertNotFound();

        auth()->logout();
        $experience = Experience::factory()->create();
        $this->postJson(route('favoritas.toggle', $experience))->assertUnauthorized();
        $this->get(route('experiencias.show', $experience))->assertSee('Ingresá para guardarla')->assertSee('wa.me', false);
    }
}
