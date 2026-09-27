<?php

namespace Tests\Feature;

use App\Enums\DietaryOption;
use App\Models\AuditLog;
use App\Models\ExperienceDate;
use App\Models\User;
use App\Services\BookingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DietaryNeedsTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function a_person_saves_their_dietary_needs_and_they_stay_out_of_the_audit_log(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('cuenta.perfil'))->assertOk()->assertSee('Celiaquía (sin TACC)');
        $this->actingAs($user)->put(route('cuenta.alimentacion.update'), ['dietary_needs' => ['sin_tacc', 'vegan'], 'food_allergies' => 'Maní'])
            ->assertSessionHasNoErrors();

        $user->refresh();
        $this->assertEquals([DietaryOption::SinTacc, DietaryOption::Vegan], $user->dietary_needs->all());
        $this->assertSame('Maní', $user->food_allergies);

        $audit = AuditLog::where('auditable_type', User::class)->where('auditable_id', (string) $user->id)->latest('id')->firstOrFail();
        $this->assertStringNotContainsString('Maní', json_encode($audit->new_values));
        $this->assertStringNotContainsString('sin_tacc', json_encode($audit->new_values));

        $this->actingAs($user)->put(route('cuenta.alimentacion.update'), ['dietary_needs' => ['carnivoro']])->assertSessionHasErrors('dietary_needs.0');
        $this->actingAs($user)->put(route('cuenta.alimentacion.update'), [])->assertSessionHasNoErrors();
        $this->assertNull($user->fresh()->dietary_needs);
    }

    #[Test]
    public function the_booking_widget_says_which_needs_the_experience_covers(): void
    {
        $date = ExperienceDate::factory()->create(['starts_at' => now()->addWeek()]);
        $date->experience->update(['dietary_options' => ['vegan', 'gluten_free']]);
        $user = User::factory()->create(['dietary_needs' => ['vegetarian', 'sin_tacc']]);

        $this->actingAs($user)->get(route('experiencias.show', $date->experience))
            ->assertOk()
            ->assertSee('Alimentación vegetariana: la cubre')
            ->assertSee('Celiaquía (sin TACC): no la indica');
    }

    #[Test]
    public function the_booking_keeps_a_copy_of_the_needs_for_the_host(): void
    {
        $date = ExperienceDate::factory()->create(['capacity' => 8]);
        $guest = User::factory()->create(['dietary_needs' => ['lactose_free'], 'food_allergies' => 'Mariscos']);

        $booking = app(BookingService::class)->request($guest, $date, 2);
        $guest->update(['dietary_needs' => null, 'food_allergies' => null]);

        $booking->refresh();
        $this->assertEquals([DietaryOption::LactoseFree], $booking->dietary_needs->all());
        $this->assertSame('Mariscos', $booking->food_allergies);
    }
}
