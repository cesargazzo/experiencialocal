<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Models\ExperienceDate;
use App\Models\User;
use App\Notifications\BookingReminderNotification;
use App\Services\BookingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

    #[Test]
    public function the_day_before_both_sides_get_a_reminder_once(): void
    {
        Notification::fake();
        $soon = ExperienceDate::factory()->create(['starts_at' => now()->addHours(20), 'capacity' => 8]);
        $soon->experience->update(['meeting_address' => 'SAN MARTIN 123, Chilecito, La Rioja', 'latitude' => -29.16, 'longitude' => -67.49]);
        $later = ExperienceDate::factory()->create(['starts_at' => now()->addDays(3), 'capacity' => 8]);
        $bookings = app(BookingService::class);
        $confirmed = $bookings->request(User::factory()->create(), $soon, 2);
        $confirmed->forceFill(['status' => BookingStatus::Confirmed])->save();
        $pending = $bookings->request(User::factory()->create(), $soon, 1);
        $notYet = $bookings->request(User::factory()->create(), $later, 1);
        $notYet->forceFill(['status' => BookingStatus::Confirmed])->save();

        $this->artisan('tinku:send-reminders')->expectsOutputToContain('Recordatorios enviados: 1.')->assertSuccessful();

        Notification::assertSentTo($confirmed->user, BookingReminderNotification::class, function ($notification) use ($confirmed) {
            $mail = $notification->toMail($confirmed->user);

            return ! $notification->forHost && collect($mail->introLines)->contains('Punto de encuentro: SAN MARTIN 123, Chilecito, La Rioja.');
        });
        Notification::assertSentTo($soon->experience->host->user, BookingReminderNotification::class, fn ($notification) => $notification->forHost);
        Notification::assertNotSentTo([$pending->user, $notYet->user], BookingReminderNotification::class);

        $this->artisan('tinku:send-reminders')->expectsOutputToContain('Recordatorios enviados: 0.');
    }
}
