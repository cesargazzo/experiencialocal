<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Enums\TeamRole;
use App\Exceptions\BookingException;
use App\Models\Booking;
use App\Models\Experience;
use App\Models\ExperienceDate;
use App\Models\SecurityEvent;
use App\Models\User;
use App\Notifications\BookingUpdatedNotification;
use App\Services\BookingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class NoShowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(Carbon::parse('2026-10-01 12:00:00', 'UTC'));
    }

    #[Test]
    public function the_host_marks_a_no_show_only_after_it_starts_and_within_the_window(): void
    {
        Notification::fake();
        $booking = $this->confirmedBooking('2026-10-02 18:00:00');
        $late = $this->confirmedBooking('2026-10-02 18:00:00');
        $host = $booking->experience->host->user;

        $this->actingAs($host)->post(route('anfitrion.reservas.no-vino', $booking))->assertSessionHasErrors('booking');
        $this->actingAs(User::factory()->create())->post(route('anfitrion.reservas.no-vino', $booking))->assertSessionHasErrors('booking');

        $this->travelTo(Carbon::parse('2026-10-02 19:00:00', 'UTC'));
        $this->actingAs($host)->get(route('anfitrion.panel'))->assertSeeInOrder(['Para cerrar', $booking->code, 'No vino']);
        $this->actingAs($host)->post(route('anfitrion.reservas.no-vino', $booking))->assertSessionHasNoErrors();

        $booking->refresh();
        $this->assertSame(BookingStatus::NoShow, $booking->status);
        $this->assertSame(0, $booking->refund_percent);
        $this->assertFalse($booking->canBeReviewed());
        Notification::assertSentTo($booking->user, BookingUpdatedNotification::class, fn ($n) => $n->event === BookingUpdatedNotification::NO_SHOW);

        $this->actingAs($booking->user)->get(route('cuenta.reservas'))->assertSee('No se presentó')->assertSee('El anfitrión marcó que no fuiste');

        $this->travelTo(Carbon::parse('2026-10-05 19:00:00', 'UTC'));
        $this->actingAs($late->experience->host->user)->post(route('anfitrion.reservas.no-vino', $late))->assertSessionHasErrors('booking');
    }

    #[Test]
    public function reaching_the_limit_blocks_new_bookings_and_hosts_see_the_count(): void
    {
        config(['tinku.no_shows.limit' => 2]);
        $guest = User::factory()->create();
        $this->noShow($guest);

        $request = app(BookingService::class)->request($guest, ExperienceDate::factory()->create(['starts_at' => '2026-10-20 18:00:00']), 1);
        $this->actingAs($request->experience->host->user)->get(route('anfitrion.panel'))->assertSee('No se presentó 1 vez en el último año');

        $this->noShow($guest);
        $this->expectException(BookingException::class);
        $this->expectExceptionMessage('no te presentaste a 2 experiencias en los últimos 12 meses');
        app(BookingService::class)->request($guest, ExperienceDate::factory()->create(['starts_at' => '2026-10-25 18:00:00']), 1);
    }

    #[Test]
    public function support_reverts_a_wrong_no_show_and_the_guest_can_book_and_review_again(): void
    {
        Notification::fake();
        config(['tinku.no_shows.limit' => 1]);
        $guest = User::factory()->create();
        $booking = $this->noShow($guest);
        $this->assertTrue($guest->hasReachedNoShowLimit());

        $this->actingAs(User::factory()->team(TeamRole::Verifier)->create())
            ->post(route('admin.usuarios.reservas.revertir-ausencia', [$guest, $booking]), ['reason' => 'El anfitrión se equivocó'])
            ->assertForbidden();

        $support = User::factory()->team(TeamRole::Support)->create();
        $this->actingAs($support)->get(route('admin.usuarios.show', $guest))->assertSee('Llegó al límite')->assertSee('Revertí la ausencia');
        $this->actingAs($support)->post(route('admin.usuarios.reservas.revertir-ausencia', [$guest, $booking]), ['reason' => ''])
            ->assertSessionHasErrorsIn('noShow', 'reason');
        $this->actingAs($support)->post(route('admin.usuarios.reservas.revertir-ausencia', [$guest, $booking]), ['reason' => 'El anfitrión se equivocó de persona'])
            ->assertSessionHasNoErrors();

        $booking->refresh();
        $this->assertSame(BookingStatus::Completed, $booking->status);
        $this->assertTrue($booking->canBeReviewed());
        $this->assertFalse($guest->hasReachedNoShowLimit());
        $this->assertTrue(SecurityEvent::where('type', 'booking.no_show_reverted')->exists());
        Notification::assertSentTo($guest, BookingUpdatedNotification::class, fn ($n) => $n->event === BookingUpdatedNotification::NO_SHOW_REVERTED);
    }

    private function confirmedBooking(string $startsAt, ?User $guest = null): Booking
    {
        $bookings = app(BookingService::class);
        $experience = Experience::factory()->create();
        $booking = $bookings->request($guest ?? User::factory()->create(), ExperienceDate::factory()->for($experience)->create(['starts_at' => $startsAt]), 1);

        return $bookings->confirm($booking, $experience->host->user);
    }

    private function noShow(User $guest): Booking
    {
        $booking = $this->confirmedBooking(now()->addDay()->toDateTimeString(), $guest);
        $now = now();
        $this->travel(25)->hours();
        app(BookingService::class)->markNoShow($booking, $booking->experience->host->user);
        $this->travelTo($now);

        return $booking;
    }
}
