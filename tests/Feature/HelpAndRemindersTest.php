<?php

namespace Tests\Feature;

use App\Enums\BookingReminder;
use App\Enums\BookingStatus;
use App\Enums\TeamRole;
use App\Enums\VerificationLevel;
use App\Models\Booking;
use App\Models\ExperienceDate;
use App\Models\User;
use App\Notifications\BookingReminderNotification;
use App\Services\BookingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class HelpAndRemindersTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function the_help_and_safety_pages_are_public_and_linked_from_the_footer(): void
    {
        $this->get(route('ayuda'))->assertOk()->assertSee('¿Cómo reservo una experiencia?');
        $this->get(route('seguridad'))->assertOk()->assertSee('Sabés con quién te encontrás')->assertSee('911');
        $this->get(route('home'))->assertSee(route('ayuda'), false)->assertSee(route('seguridad'), false);
    }

    private function confirmedBooking(string $startsAt, string $confirmedAt = '2026-09-20 12:00:00', array $userAttributes = []): Booking
    {
        $date = ExperienceDate::factory()->create(['starts_at' => $startsAt, 'capacity' => 8]);
        $booking = app(BookingService::class)->request(User::factory()->level(VerificationLevel::Document)->create($userAttributes), $date, 2);
        $booking->forceFill(['status' => BookingStatus::Confirmed, 'confirmed_at' => $confirmedAt])->save();

        return $booking->fresh();
    }

    #[Test]
    public function a_week_before_a_day_before_and_the_same_day_each_go_out_once(): void
    {
        Notification::fake();
        // Experiencia el sábado 10 a las 20:30 de Argentina (23:30 UTC).
        $this->travelTo(Carbon::parse('2026-10-01 12:00:00'));
        $booking = $this->confirmedBooking('2026-10-10 23:30:00');
        $guest = $booking->user;
        $host = $booking->experience->host->user;
        $sentTo = fn (User $user, BookingReminder $kind) => Notification::sent($user, BookingReminderNotification::class)->filter(fn ($n) => $n->reminder === $kind)->count();

        $this->artisan('tinku:send-reminders')->expectsOutputToContain('Recordatorios enviados: 0.');

        $this->travelTo(Carbon::parse('2026-10-04 12:00:00'));
        $this->artisan('tinku:send-reminders')->expectsOutputToContain('Recordatorios enviados: 1.');
        $this->artisan('tinku:send-reminders')->expectsOutputToContain('Recordatorios enviados: 0.');
        $this->assertSame(1, $sentTo($guest, BookingReminder::Week));
        $this->assertSame(0, Notification::sent($host, BookingReminderNotification::class)->count(), 'El de la semana es solo para quien reservó.');

        $this->travelTo(Carbon::parse('2026-10-10 00:00:00'));
        $this->artisan('tinku:send-reminders')->expectsOutputToContain('Recordatorios enviados: 1.');
        $this->assertSame(1, $sentTo($guest, BookingReminder::Day));
        $this->assertSame(1, $sentTo($host, BookingReminder::Day));

        // El mismo día sale desde las 8 de la mañana hora local (11 UTC), no antes.
        $this->travelTo(Carbon::parse('2026-10-10 10:30:00'));
        $this->artisan('tinku:send-reminders')->expectsOutputToContain('Recordatorios enviados: 0.');
        $this->travelTo(Carbon::parse('2026-10-10 11:05:00'));
        $this->artisan('tinku:send-reminders')->expectsOutputToContain('Recordatorios enviados: 1.');
        $this->assertSame(1, $sentTo($guest, BookingReminder::Today));
        $this->assertSame(['week', 'day', 'today'], $booking->fresh()->reminders_sent);
    }

    #[Test]
    public function a_booking_made_the_same_day_gets_only_the_closest_reminder_and_pending_ones_none(): void
    {
        Notification::fake();
        $this->travelTo(Carbon::parse('2026-10-10 13:00:00'));
        $late = $this->confirmedBooking('2026-10-10 23:30:00', confirmedAt: '2026-10-10 12:30:00');
        $pending = app(BookingService::class)->request(User::factory()->level(VerificationLevel::Document)->create(), $late->date, 1);

        $this->artisan('tinku:send-reminders')->expectsOutputToContain('Recordatorios enviados: 1.');
        Notification::assertSentTo($late->user, BookingReminderNotification::class, fn ($n) => $n->reminder === BookingReminder::Today);
        Notification::assertNotSentTo($pending->user, BookingReminderNotification::class);
        $this->assertSame(['week', 'day', 'today'], $late->fresh()->reminders_sent);
    }

    #[Test]
    public function the_reminder_speaks_the_persons_language_and_the_mail_brings_the_calendar_file(): void
    {
        $this->travelTo(Carbon::parse('2026-10-09 23:40:00'));
        $booking = $this->confirmedBooking('2026-10-10 23:30:00', userAttributes: ['locale' => 'en', 'first_name' => 'Ana']);
        $booking->experience->update(['title' => 'Empanadas en el patio', 'meeting_address' => 'SAN MARTIN 123, Chilecito, La Rioja']);

        $this->artisan('tinku:send-reminders');

        $notice = $booking->user->fresh()->notifications()->first();
        $this->assertSame('Tomorrow is your experience: Empanadas en el patio', $notice->data['title']);
        $this->assertStringContainsString('Saturday, October 10, 2026', $notice->data['body']);

        $mail = (new BookingReminderNotification($booking->fresh(), BookingReminder::Day))->toMail($booking->user);
        $this->assertContains('Punto de encuentro: SAN MARTIN 123, Chilecito, La Rioja.', $mail->introLines);
        $ics = $mail->rawAttachments[0]['data'];
        $this->assertStringContainsString('DTSTART:20261010T233000Z', $ics);
        $this->assertStringContainsString('LOCATION:SAN MARTIN 123\, Chilecito\, La Rioja', $ics);

        $this->actingAs($booking->user)->get(route('cuenta.reservas.calendario', $booking))->assertOk()->assertHeader('Content-Type', 'text/calendar; charset=UTF-8');
        $this->actingAs(User::factory()->create())->get(route('cuenta.reservas.calendario', $booking))->assertNotFound();
    }

    #[Test]
    public function the_team_previews_the_reminder_mails_without_sending_them(): void
    {
        Notification::fake();
        $booking = $this->confirmedBooking(now()->addDays(3)->toDateTimeString());

        $this->actingAs(User::factory()->admin()->create())->get(route('admin.mails.preview', 'recordatorio-semana'))
            ->assertOk()->assertSee($booking->code)->assertSee('Cancelá desde Tus reservas');
        $this->actingAs(User::factory()->team(TeamRole::Moderator)->create())->get(route('admin.mails.preview', 'recordatorio-dia'))->assertForbidden();
        Notification::assertNotSentTo($booking->user, BookingReminderNotification::class);
    }
}
