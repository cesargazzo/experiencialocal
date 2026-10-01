<?php

namespace App\Enums;

use App\Models\Booking;
use Carbon\CarbonInterface;

/**
 * Los recordatorios de una reserva confirmada: una semana antes, un día antes y
 * el mismo día. Se manda el más cercano que corresponda y no se repite.
 */
enum BookingReminder: string
{
    case Week = 'week';
    case Day = 'day';
    case Today = 'today';

    /** Hora local desde la que sale el recordatorio del mismo día. */
    public const TODAY_FROM_HOUR = 8;

    /** El de la semana no se manda si la reserva se confirmó hace menos que esto: acaba de recibir la confirmación. */
    public const WEEK_SKIP_AFTER_CONFIRMATION_HOURS = 48;

    /** Cuál toca ahora, o null si ninguno. */
    public static function dueFor(Booking $booking, ?CarbonInterface $now = null): ?self
    {
        $now ??= now();
        $startsAt = $booking->date->starts_at;
        if ($startsAt->lte($now)) {
            return null;
        }

        $sent = $booking->reminders_sent ?? [];
        $localNow = $now->copy()->timezone($booking->experience->timezone());
        $isToday = $booking->date->localStart()->isSameDay($localNow);

        $due = match (true) {
            $isToday && $localNow->hour >= self::TODAY_FROM_HOUR => self::Today,
            $startsAt->lte($now->copy()->addDay()) => self::Day,
            $startsAt->lte($now->copy()->addWeek()) && ! $booking->confirmed_at?->gt($now->copy()->subHours(self::WEEK_SKIP_AFTER_CONFIRMATION_HOURS)) => self::Week,
            default => null,
        };

        return $due && ! in_array($due->value, $sent, true) ? $due : null;
    }

    /** Al mandar uno, los anteriores ya no tienen sentido. */
    public function covers(): array
    {
        return match ($this) {
            self::Week => [self::Week->value],
            self::Day => [self::Week->value, self::Day->value],
            self::Today => [self::Week->value, self::Day->value, self::Today->value],
        };
    }

    /** Al anfitrión le llegan el del día anterior y el del mismo día. */
    public function notifiesHost(): bool
    {
        return $this !== self::Week;
    }
}
