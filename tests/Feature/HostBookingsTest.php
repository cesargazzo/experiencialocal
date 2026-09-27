<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Models\ExperienceDate;
use App\Models\User;
use App\Services\BookingService;
use Database\Seeders\PlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class HostBookingsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PlanSeeder::class);
        $this->travelTo(Carbon::parse('2026-10-01 12:00:00', 'UTC'));
    }

    #[Test]
    public function the_host_sees_who_booked_with_a_count_in_the_header_and_confirms(): void
    {
        $date = ExperienceDate::factory()->create(['starts_at' => Carbon::parse('2026-10-04 23:30:00', 'UTC'), 'capacity' => 8]);
        $host = $date->experience->host->user;
        $guest = User::factory()->create(['name' => 'Lucía Paz', 'dietary_needs' => ['sin_tacc'], 'food_allergies' => 'Maní']);
        $booking = app(BookingService::class)->request($guest, $date, 2, 'Es el cumpleaños de mi mamá');

        $this->assertSame(1, $host->notifications()->count());
        $this->actingAs($host)->get(route('home'))->assertSee('1 reserva por confirmar');
        $this->actingAs($host)->get(route('anfitrion.panel'))
            ->assertOk()
            ->assertSeeInOrder(['Reservas por confirmar', 'Lucía Paz', 'Dom. 4 oct. · 20:30', '2 personas'])
            ->assertSee('Celiaquía (sin TACC)')
            ->assertSee('Alergias: Maní')
            ->assertSee('Es el cumpleaños de mi mamá');

        $this->actingAs($host)->post(route('anfitrion.reservas.confirmar', $booking))->assertSessionHasNoErrors();

        $this->assertSame(BookingStatus::Confirmed, $booking->fresh()->status);
        $this->assertSame('Reserva confirmada: '.$date->experience->title, $guest->notifications()->sole()->data['title']);
        $this->actingAs($host)->get(route('home'))->assertDontSee('reserva por confirmar');
        $this->actingAs($host)->get(route('anfitrion.panel'))->assertSeeInOrder(['Próximas confirmadas', 'Lucía Paz']);
    }

    #[Test]
    public function the_host_can_decline_and_the_seats_are_released(): void
    {
        $date = ExperienceDate::factory()->create(['starts_at' => now()->addWeek(), 'capacity' => 8]);
        $guest = User::factory()->create();
        $booking = app(BookingService::class)->request($guest, $date, 3);

        $this->actingAs($date->experience->host->user)->post(route('anfitrion.reservas.rechazar', $booking))->assertSessionHasNoErrors();

        $this->assertSame(BookingStatus::Declined, $booking->fresh()->status);
        $this->assertSame(0, $date->fresh()->booked_count);
        $this->assertStringStartsWith('No se pudo confirmar tu reserva', $guest->notifications()->sole()->data['title']);
    }

    #[Test]
    public function another_host_cannot_confirm_someone_elses_booking(): void
    {
        $date = ExperienceDate::factory()->create(['starts_at' => now()->addWeek(), 'capacity' => 8]);
        $booking = app(BookingService::class)->request(User::factory()->create(), $date, 1);
        $otherHost = ExperienceDate::factory()->create()->experience->host->user;

        $this->actingAs($otherHost)->post(route('anfitrion.reservas.confirmar', $booking))
            ->assertSessionHasErrors(['booking' => 'Esta reserva no es de una experiencia tuya.']);
        $this->assertSame(BookingStatus::Requested, $booking->fresh()->status);
    }
}
