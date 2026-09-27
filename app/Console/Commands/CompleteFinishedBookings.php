<?php

namespace App\Console\Commands;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Notifications\ReviewRequestNotification;
use App\Services\BookingService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/**
 * Las reservas confirmadas cuya experiencia ya terminó pasan a realizadas y
 * se invita a quien fue a dejar su opinión.
 */
#[Signature('tinku:complete-bookings')]
#[Description('Marca como realizadas las reservas confirmadas que ya pasaron y pide la opinión')]
class CompleteFinishedBookings extends Command
{
    public function handle(BookingService $bookings): int
    {
        $count = 0;

        Booking::query()
            ->where('status', BookingStatus::Confirmed)
            ->whereHas('date', fn ($q) => $q->where('ends_at', '<', now()))
            ->with(['user', 'experience.host.user', 'date'])
            ->chunkById(100, function ($finished) use ($bookings, &$count) {
                foreach ($finished as $booking) {
                    $bookings->complete($booking);
                    $booking->user->notify(new ReviewRequestNotification($booking));
                    $count++;
                }
            });

        $this->info("Reservas realizadas: {$count}.");

        return self::SUCCESS;
    }
}
