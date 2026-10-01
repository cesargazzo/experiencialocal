<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Enums\VerificationLevel;
use App\Livewire\HostCalendar;
use App\Models\Experience;
use App\Models\ExperienceDate;
use App\Models\User;
use App\Services\BookingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class HostToolsTest extends TestCase
{
    use RefreshDatabase;

    private function book(ExperienceDate $date, int $guests, BookingStatus $status): void
    {
        $booking = app(BookingService::class)->request(User::factory()->level(VerificationLevel::Document)->create(), $date, $guests);
        $booking->update(['status' => $status]);
    }

    #[Test]
    public function earnings_add_up_completed_and_upcoming_confirmed_bookings(): void
    {
        $this->travelTo(Carbon::parse('2026-10-01 12:00:00'));
        $experience = Experience::factory()->create(['price' => 10000]);
        $past = ExperienceDate::factory()->for($experience)->create(['starts_at' => '2026-10-05 23:00:00']);
        $future = ExperienceDate::factory()->for($experience)->create(['starts_at' => '2026-11-05 23:00:00']);
        $this->book($past, 2, BookingStatus::Confirmed);
        $this->book($future, 3, BookingStatus::Confirmed);

        $this->travelTo(Carbon::parse('2026-10-20 12:00:00'));
        $experience->bookings()->where('experience_date_id', $past->id)->update(['status' => BookingStatus::Completed]);
        $payout = fn (int $guests) => (float) $experience->bookings()->where('guests', $guests)->value('host_payout');

        $response = $this->actingAs($experience->host->user)->get(route('anfitrion.ganancias'))->assertOk();
        $this->assertEquals($payout(2), $response->viewData('earned'));
        $this->assertEquals($payout(3), $response->viewData('toCollect'));
        $response->assertSee('Octubre 2026');
    }

    #[Test]
    public function the_calendar_shows_the_month_and_lets_the_host_close_sales_and_change_seats(): void
    {
        $this->travelTo(Carbon::parse('2026-10-01 12:00:00'));
        $experience = Experience::factory()->create(['title' => 'Asado en la finca']);
        $date = ExperienceDate::factory()->for($experience)->create(['starts_at' => '2026-10-10 23:30:00', 'capacity' => 6]);
        $this->book($date, 2, BookingStatus::Confirmed);
        $host = $experience->host->user;

        Livewire::actingAs($host)->test(HostCalendar::class)
            ->assertSee('Octubre 2026')->assertSee('Asado en la finca')
            ->call('select', $date->id)
            ->set('capacity', 1)->call('saveCapacity')->assertHasErrors('capacity')
            ->set('capacity', 10)->call('saveCapacity')->assertHasNoErrors()
            ->call('toggleSales')
            ->call('remove')->assertHasErrors('remove');

        $date->refresh();
        $this->assertSame(10, $date->capacity);
        $this->assertSame('closed', $date->status);
        $this->assertFalse($date->isOpen(), 'Con la venta cerrada no se puede reservar.');

        $other = Experience::factory()->create();
        $foreign = ExperienceDate::factory()->for($other)->create();
        Livewire::actingAs($host)->test(HostCalendar::class)->call('select', $foreign->id)->assertNotFound();
    }

    #[Test]
    public function stats_count_one_visit_per_person_and_day_and_not_the_hosts_own(): void
    {
        $experience = Experience::factory()->create(['title' => 'Taller de telar']);
        $host = $experience->host->user;

        $this->get(route('experiencias.show', $experience));
        $this->get(route('experiencias.show', $experience));
        $this->withHeader('User-Agent', 'Googlebot/2.1')->get(route('experiencias.show', $experience));
        $this->actingAs($host)->get(route('experiencias.show', $experience));

        $this->actingAs($host)->get(route('anfitrion.estadisticas', ['dias' => 7]))->assertOk()
            ->assertSee('Taller de telar')
            ->assertViewHas('totals', fn (array $totals) => $totals['views'] === 1);
    }
}
