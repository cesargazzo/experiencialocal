<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Exceptions\BookingException;
use App\Models\Booking;
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

    #[Test]
    public function a_person_cannot_book_the_same_date_twice_but_can_cancel_and_book_again(): void
    {
        $date = ExperienceDate::factory()->create(['starts_at' => now()->addWeek(), 'capacity' => 10]);
        $guest = User::factory()->create();
        $bookings = app(BookingService::class);
        $first = $bookings->request($guest, $date, 2);

        try {
            $bookings->request($guest, $date, 1);
            $this->fail('No debería permitir una segunda reserva para la misma fecha.');
        } catch (BookingException $e) {
            $this->assertStringContainsString("Ya tenés una reserva para esta fecha (código {$first->code})", $e->getMessage());
        }
        $this->assertSame(2, $date->fresh()->booked_count);

        $this->actingAs($guest)->get(route('experiencias.show', $date->experience))->assertSee('Ya reservaste esta fecha');

        $this->actingAs($guest)->post(route('cuenta.reservas.cancelar', $first))->assertSessionHasNoErrors();
        $this->assertSame(BookingStatus::Cancelled, $first->fresh()->status);
        $this->assertSame(0, $date->fresh()->booked_count);

        $this->assertSame(4, $bookings->request($guest, $date, 4)->guests);
    }

    #[Test]
    public function nobody_can_cancel_someone_elses_booking(): void
    {
        $date = ExperienceDate::factory()->create(['starts_at' => now()->addWeek(), 'capacity' => 10]);
        $booking = app(BookingService::class)->request(User::factory()->create(), $date, 1);

        $this->actingAs(User::factory()->create())->post(route('cuenta.reservas.cancelar', $booking))->assertNotFound();
        $this->assertSame(BookingStatus::Requested, $booking->fresh()->status);
    }

    #[Test]
    public function the_cleanup_keeps_one_booking_per_person_and_date_and_frees_the_rest(): void
    {
        $date = ExperienceDate::factory()->create(['starts_at' => now()->addWeek(), 'capacity' => 10, 'booked_count' => 6]);
        $guest = User::factory()->create();
        $base = ['experience_id' => $date->experience_id, 'experience_date_id' => $date->id, 'user_id' => $guest->id, 'guests' => 2, 'unit_price' => 1000, 'subtotal' => 2000,
            'service_fee_rate' => 0.08, 'service_fee' => 160, 'total' => 2160, 'commission_rate' => 0.18, 'commission_amount' => 360, 'host_payout' => 1640, 'currency' => 'ARS', 'status' => 'requested'];
        $kept = Booking::create($base);
        $duplicates = [Booking::create($base), Booking::create($base)];

        (require database_path('migrations/2026_09_27_045042_cancel_duplicate_active_bookings.php'))->up();

        $this->assertSame(BookingStatus::Requested, $kept->fresh()->status);
        foreach ($duplicates as $duplicate) {
            $this->assertSame(BookingStatus::Cancelled, $duplicate->fresh()->status);
        }
        $this->assertSame(2, $date->fresh()->booked_count);
    }
}
