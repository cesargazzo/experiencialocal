<?php

namespace App\Console\Commands;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Notifications\BookingReminderNotification;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/**
 * Un día antes: a quien reservó, con el punto de encuentro; al anfitrión, a quién recibe.
 */
#[Signature('tinku:send-reminders')]
#[Description('Manda el recordatorio de las reservas confirmadas que empiezan en las próximas 24 horas')]
class SendBookingReminders extends Command
{
    public function handle(): int
    {
        $count = 0;

        Booking::query()
            ->where('status', BookingStatus::Confirmed)
            ->whereNull('reminder_sent_at')
            ->whereHas('date', fn ($q) => $q->whereBetween('starts_at', [now(), now()->addDay()]))
            ->with(['user', 'date', 'experience.host.user', 'experience.province'])
            ->chunkById(100, function ($bookings) use (&$count) {
                foreach ($bookings as $booking) {
                    $booking->user->notify(new BookingReminderNotification($booking));
                    $booking->experience->host->user->notify(new BookingReminderNotification($booking, forHost: true));
                    $booking->forceFill(['reminder_sent_at' => now()])->save();
                    $count++;
                }
            });

        $this->info("Recordatorios enviados: {$count}.");

        return self::SUCCESS;
    }
}
