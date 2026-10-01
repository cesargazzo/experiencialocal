<?php

namespace App\Console\Commands;

use App\Enums\BookingReminder;
use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Notifications\BookingReminderNotification;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/**
 * Recordatorios de las reservas confirmadas: una semana antes, un día antes y el
 * mismo día a quien reservó; el del día anterior y el del mismo día, también al anfitrión.
 */
#[Signature('tinku:send-reminders')]
#[Description('Manda los recordatorios de las reservas confirmadas de los próximos siete días')]
class SendBookingReminders extends Command
{
    public function handle(): int
    {
        $count = 0;

        Booking::query()
            ->where('status', BookingStatus::Confirmed)
            ->whereHas('date', fn ($q) => $q->whereBetween('starts_at', [now(), now()->addWeek()]))
            ->with(['user', 'date', 'experience.host.user', 'experience.province'])
            ->chunkById(100, function ($bookings) use (&$count) {
                foreach ($bookings as $booking) {
                    $reminder = BookingReminder::dueFor($booking);
                    if (! $reminder) {
                        continue;
                    }

                    $booking->user->notify(new BookingReminderNotification($booking, $reminder));
                    if ($reminder->notifiesHost()) {
                        $booking->experience->host->user->notify(new BookingReminderNotification($booking, $reminder, forHost: true));
                    }
                    $booking->forceFill(['reminders_sent' => array_values(array_unique([...($booking->reminders_sent ?? []), ...$reminder->covers()]))])->save();
                    $count++;
                }
            });

        $this->info("Recordatorios enviados: {$count}.");

        return self::SUCCESS;
    }
}
