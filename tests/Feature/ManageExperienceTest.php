<?php

namespace Tests\Feature;

use App\Enums\ExperienceStatus;
use App\Livewire\ManageExperience;
use App\Models\Experience;
use App\Models\ExperienceDate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ManageExperienceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();
        $this->travelTo(Carbon::parse('2026-10-01 12:00:00', 'UTC'));
    }

    #[Test]
    public function only_the_owner_can_edit_an_experience(): void
    {
        $experience = Experience::factory()->create();

        $this->actingAs($experience->host->user)->get(route('anfitrion.experiencias.editar', $experience))->assertOk()->assertSee('Fechas');
        $this->actingAs(User::factory()->create())->get(route('anfitrion.experiencias.editar', $experience))->assertForbidden();
    }

    #[Test]
    public function price_and_food_changes_apply_right_away_but_content_changes_go_back_to_review(): void
    {
        $experience = Experience::factory()->create(['price' => 30000]);

        Livewire::actingAs($experience->host->user)->test(ManageExperience::class, ['experience' => $experience])
            ->set('price', 35000)
            ->set('dietary_options', ['vegan'])
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('notice', 'Guardamos los cambios.');
        $this->assertSame(ExperienceStatus::Published, $experience->fresh()->status);
        $this->assertEquals(35000, $experience->fresh()->price);

        Livewire::actingAs($experience->host->user)->test(ManageExperience::class, ['experience' => $experience->fresh()])
            ->set('description', str_repeat('Otra descripción con más detalles de lo que vamos a cocinar juntos. ', 2))
            ->call('save')
            ->assertHasNoErrors();

        $experience->refresh();
        $this->assertSame(ExperienceStatus::InReview, $experience->status);
        $this->assertNull($experience->approved_at);
        $this->assertNull($experience->published_at);
    }

    #[Test]
    public function a_rejected_experience_goes_back_to_review_once_corrected(): void
    {
        $experience = Experience::factory()->create(['status' => ExperienceStatus::Draft, 'published_at' => null, 'approved_at' => null, 'rejection_reason' => 'La foto es de stock.']);

        Livewire::actingAs($experience->host->user)->test(ManageExperience::class, ['experience' => $experience])
            ->assertSee('Motivo: La foto es de stock.')
            ->set('summary', 'Empanadas de verdad, en el patio de casa.')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame(ExperienceStatus::InReview, $experience->fresh()->status);
        $this->assertNull($experience->fresh()->rejection_reason);
    }

    #[Test]
    public function a_host_adds_one_date_or_several_by_weekday_in_local_time(): void
    {
        $experience = Experience::factory()->create(['duration_minutes' => 180]);
        $component = Livewire::actingAs($experience->host->user)->test(ManageExperience::class, ['experience' => $experience]);

        $component->set('single_date', '2026-10-09')->set('time', '20:30')->call('addDates')->assertHasNoErrors();
        $this->assertSame('2026-10-09 23:30:00', $experience->dates()->first()->starts_at->utc()->format('Y-m-d H:i:s'));

        // Viernes y sábados de octubre desde el 2: 2, 3, 9 (ya estaba), 10, 16, 17, 23, 24, 30, 31.
        $component->set('date_mode', 'range')
            ->set('range_from', '2026-10-02')
            ->set('range_to', '2026-10-31')
            ->set('weekdays', ['5', '6'])
            ->call('addDates')
            ->assertHasNoErrors()
            ->assertSet('notice', 'Sumamos 9 fechas nuevas (1 ya estaban).');

        $this->assertSame(10, $experience->dates()->count());
        $this->assertTrue($experience->dates()->get()->every(fn ($date) => $date->localStart()->format('H:i') === '20:30' && $date->ends_at->diffInMinutes($date->starts_at, true) === 180.0));
    }

    #[Test]
    public function too_many_dates_or_an_empty_range_are_explained(): void
    {
        $experience = Experience::factory()->create();

        Livewire::actingAs($experience->host->user)->test(ManageExperience::class, ['experience' => $experience])
            ->set('date_mode', 'range')
            ->set('range_from', '2026-10-02')
            ->set('range_to', '2027-09-30')
            ->set('weekdays', ['1', '2', '3', '4', '5', '6', '7'])
            ->call('addDates')
            ->assertHasErrors('dates')
            ->set('weekdays', [])
            ->call('addDates')
            ->assertHasErrors(['weekdays' => 'Elegí al menos un día de la semana.']);

        $this->assertSame(0, $experience->dates()->count());
    }

    #[Test]
    public function a_date_with_bookings_cannot_be_removed(): void
    {
        $experience = Experience::factory()->create();
        $free = ExperienceDate::factory()->for($experience)->create(['starts_at' => now()->addWeek(), 'booked_count' => 0]);
        $booked = ExperienceDate::factory()->for($experience)->create(['starts_at' => now()->addWeeks(2), 'booked_count' => 2]);

        Livewire::actingAs($experience->host->user)->test(ManageExperience::class, ['experience' => $experience])
            ->call('removeDate', $free->id)
            ->assertHasNoErrors()
            ->call('removeDate', $booked->id)
            ->assertHasErrors('dates');

        $this->assertNull($free->fresh());
        $this->assertNotNull($booked->fresh());
    }
}
