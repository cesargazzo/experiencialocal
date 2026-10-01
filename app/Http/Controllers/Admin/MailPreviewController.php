<?php

namespace App\Http\Controllers\Admin;

use App\Enums\BookingReminder;
use App\Enums\BookingStatus;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Notifications\BookingReminderNotification;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Http\Request;

/**
 * Cómo se ven los mails antes de prenderlos: se arman con una reserva real
 * y se muestran en pantalla, sin mandar nada.
 */
class MailPreviewController extends Controller
{
    /** @var array<string, string> */
    public const TYPES = [
        'recordatorio-semana' => 'Recordatorio: una semana antes',
        'recordatorio-dia' => 'Recordatorio: un día antes',
        'recordatorio-hoy' => 'Recordatorio: el mismo día',
        'recordatorio-anfitrion' => 'Recordatorio al anfitrión',
    ];

    public function __invoke(Request $request, string $type): Htmlable
    {
        abort_unless(array_key_exists($type, self::TYPES), 404);

        $booking = Booking::query()->whereIn('status', [BookingStatus::Confirmed, BookingStatus::Completed])
            ->with(['user', 'date', 'experience.host.user', 'experience.province'])->latest()->first();
        abort_if($booking === null, 404, 'Hace falta al menos una reserva confirmada para ver los mails.');

        $notification = match ($type) {
            'recordatorio-semana' => new BookingReminderNotification($booking, BookingReminder::Week),
            'recordatorio-dia' => new BookingReminderNotification($booking, BookingReminder::Day),
            'recordatorio-hoy' => new BookingReminderNotification($booking, BookingReminder::Today),
            'recordatorio-anfitrion' => new BookingReminderNotification($booking, BookingReminder::Day, forHost: true),
        };
        $recipient = $notification->forHost ? $booking->experience->host->user : $booking->user;

        return $notification->toMail($recipient)->render();
    }
}
