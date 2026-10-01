<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Enums\CancellationPolicy;
use App\Enums\ExperienceStatus;
use App\Livewire\ManageExperience;
use App\Models\Booking;
use App\Models\Experience;
use App\Models\ExperienceDate;
use App\Models\User;
use App\Services\BookingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CancellationPolicyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(Carbon::parse('2026-10-01 12:00:00', 'UTC'));
    }

    #[Test]
    public function each_policy_refunds_according_to_how_long_before_it_is_cancelled(): void
    {
        $this->assertSame(100, CancellationPolicy::Flexible->refundPercent(24));
        $this->assertSame(0, CancellationPolicy::Flexible->refundPercent(23.9));

        $this->assertSame(100, CancellationPolicy::Moderate->refundPercent(72));
        $this->assertSame(50, CancellationPolicy::Moderate->refundPercent(30));
        $this->assertSame(0, CancellationPolicy::Moderate->refundPercent(10));

        $this->assertSame(100, CancellationPolicy::Strict->refundPercent(168));
        $this->assertSame(50, CancellationPolicy::Strict->refundPercent(100));
        $this->assertSame(0, CancellationPolicy::Strict->refundPercent(48));

        $this->assertSame('Sin costo hasta 3 días antes. Se devuelve el 50% hasta 24 horas antes. Después, o si no te presentás, no hay devolución.', CancellationPolicy::Moderate->summary());
    }

    #[Test]
    public function the_host_chooses_the_policy_and_it_does_not_send_the_experience_back_to_review(): void
    {
        $experience = Experience::factory()->create();
        $this->assertSame(CancellationPolicy::Moderate, $experience->cancellation_policy);

        Livewire::actingAs($experience->host->user)->test(ManageExperience::class, ['experience' => $experience])
            ->set('cancellation_policy', 'strict')
            ->call('save')
            ->assertHasNoErrors();
        $this->assertSame(CancellationPolicy::Strict, $experience->fresh()->cancellation_policy);
        $this->assertSame(ExperienceStatus::Published, $experience->fresh()->status);

        Livewire::actingAs($experience->host->user)->test(ManageExperience::class, ['experience' => $experience->fresh()])
            ->set('cancellation_policy', 'free-for-all')
            ->call('save')
            ->assertHasErrors('cancellation_policy');

        $this->get(route('experiencias.show', $experience))->assertSee('Sin costo hasta 7 días antes.');
    }

    #[Test]
    public function a_booking_keeps_the_policy_it_was_made_with_and_the_guest_cancellation_records_the_refund(): void
    {
        $experience = Experience::factory()->create(['cancellation_policy' => CancellationPolicy::Flexible]);
        $booking = $this->confirmedBooking($experience, '2026-10-02 18:00:00');
        $experience->update(['cancellation_policy' => CancellationPolicy::Strict]);

        $this->assertSame(CancellationPolicy::Flexible, $booking->fresh()->cancellation_policy);
        $this->actingAs($booking->user)->get(route('cuenta.reservas'))
            ->assertSee('Cancelación flexible:')
            ->assertSee('Si cancelás ahora se te devuelve todo.');

        $this->travelTo(Carbon::parse('2026-10-02 06:00:00', 'UTC'));
        $this->actingAs($booking->user)->get(route('cuenta.reservas'))->assertSee('Si cancelás ahora no hay devolución.');
        $this->actingAs($booking->user)->post(route('cuenta.reservas.cancelar', $booking))->assertSessionHasNoErrors();

        $booking->refresh();
        $this->assertSame(BookingStatus::Cancelled, $booking->status);
        $this->assertSame(0, $booking->refund_percent);
        $this->actingAs($booking->user)->get(route('cuenta.reservas'))->assertSee('Por la política de cancelación no hubo devolución.');
    }

    #[Test]
    public function a_cancellation_by_the_host_or_before_confirmation_refunds_everything(): void
    {
        $experience = Experience::factory()->create(['cancellation_policy' => CancellationPolicy::Strict]);
        $bookings = app(BookingService::class);

        $confirmed = $this->confirmedBooking($experience, '2026-10-02 18:00:00');
        $bookings->cancel($confirmed, $experience->host->user);
        $this->assertSame(100, $confirmed->fresh()->refund_percent);

        $requested = $bookings->request(User::factory()->create(), ExperienceDate::factory()->for($experience)->create(['starts_at' => '2026-10-02 18:00:00']), 1);
        $bookings->cancel($requested, $requested->user);
        $this->assertSame(100, $requested->fresh()->refund_percent);
    }

    private function confirmedBooking(Experience $experience, string $startsAt): Booking
    {
        $bookings = app(BookingService::class);
        $booking = $bookings->request(User::factory()->create(), ExperienceDate::factory()->for($experience)->create(['starts_at' => $startsAt]), 1);

        return $bookings->confirm($booking, $experience->host->user);
    }
}
