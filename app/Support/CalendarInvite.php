<?php

namespace App\Support;

use App\Models\Booking;

/**
 * Archivo .ics de una reserva, para sumarla a Google Calendar, Apple o Outlook.
 * La dirección exacta va solo si la reserva está confirmada.
 */
class CalendarInvite
{
    public static function forBooking(Booking $booking): string
    {
        $experience = $booking->experience;
        $date = $booking->date;
        $location = $booking->status->value === 'confirmed' ? ($experience->meeting_address ?? $experience->placeLabel()) : $experience->placeLabel();

        $lines = [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//Tinku//Reservas//ES',
            'CALSCALE:GREGORIAN',
            'METHOD:PUBLISH',
            'BEGIN:VEVENT',
            'UID:'.$booking->code.'@'.parse_url((string) config('app.url'), PHP_URL_HOST),
            'DTSTAMP:'.now()->utc()->format('Ymd\THis\Z'),
            'DTSTART:'.$date->starts_at->copy()->utc()->format('Ymd\THis\Z'),
            'DTEND:'.($date->ends_at ?? $date->starts_at->copy()->addMinutes($experience->duration_minutes))->copy()->utc()->format('Ymd\THis\Z'),
            'SUMMARY:'.self::escape($experience->title.' · Tinku'),
            'LOCATION:'.self::escape($location),
            'DESCRIPTION:'.self::escape(__('Reserva :code para :guests. Mirala en :url', ['code' => $booking->code, 'guests' => plural_es($booking->guests, __('persona'), __('personas')), 'url' => route('cuenta.reservas')])),
            'URL:'.route('cuenta.reservas'),
            'BEGIN:VALARM',
            'TRIGGER:-PT2H',
            'ACTION:DISPLAY',
            'DESCRIPTION:'.self::escape($experience->title),
            'END:VALARM',
            'END:VEVENT',
            'END:VCALENDAR',
        ];

        return implode("\r\n", array_map(self::fold(...), $lines))."\r\n";
    }

    private static function escape(string $text): string
    {
        return str_replace(['\\', ';', ',', "\r\n", "\n"], ['\\\\', '\;', '\,', '\n', '\n'], $text);
    }

    /** Las líneas de un .ics no pueden pasar de 75 bytes: se cortan y siguen con un espacio. */
    private static function fold(string $line): string
    {
        $parts = [];
        while (strlen($line) > 75) {
            $cut = 75;
            while ($cut > 0 && (ord($line[$cut]) & 0xC0) === 0x80) {
                $cut--;
            }
            $parts[] = substr($line, 0, $cut);
            $line = ' '.substr($line, $cut);
        }
        $parts[] = $line;

        return implode("\r\n", $parts);
    }
}
