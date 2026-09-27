<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Models\Experience;
use App\Models\ExperienceDate;
use App\Models\User;
use App\Services\BookingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AccountBookingsTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function a_person_sees_upcoming_bookings_apart_from_their_history(): void
    {
        $this->travelTo(Carbon::parse('2026-10-01 12:00:00', 'UTC'));
        $guest = User::factory()->create();
        $bookings = app(BookingService::class);

        $upcoming = $bookings->request($guest, ExperienceDate::factory()->for(Experience::factory()->state(['title' => 'Empanadas en Chilecito']))
            ->create(['starts_at' => Carbon::parse('2026-10-10 23:30:00', 'UTC')]), 2);
        $cancelled = $bookings->request($guest, ExperienceDate::factory()->for(Experience::factory()->state(['title' => 'Paseo por Famatina']))
            ->create(['starts_at' => Carbon::parse('2026-10-20 13:00:00', 'UTC')]), 1);
        $cancelled->update(['status' => BookingStatus::Cancelled]);
        $bookings->request(User::factory()->create(), ExperienceDate::factory()->for(Experience::factory()->state(['title' => 'Reserva de otra persona']))->create(), 1);

        $this->actingAs($guest)->get(route('cuenta.reservas'))
            ->assertOk()
            ->assertSeeInOrder(['Próximas', 'Empanadas en Chilecito', 'Solicitada', '20:30', '2 personas', $upcoming->code, 'Historial', 'Paseo por Famatina', 'Cancelada'])
            ->assertDontSee('Reserva de otra persona');
    }

    #[Test]
    public function without_bookings_it_invites_to_look_for_one(): void
    {
        $this->actingAs(User::factory()->create())->get(route('cuenta.reservas'))
            ->assertOk()
            ->assertSee('No tenés reservas próximas')
            ->assertDontSee('Historial');
    }

    #[Test]
    public function the_bookings_page_requires_login(): void
    {
        $this->get(route('cuenta.reservas'))->assertRedirect(route('login'));
    }
}
