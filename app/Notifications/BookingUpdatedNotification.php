<?php

namespace App\Notifications;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Novedades de una reserva: al anfitrión cuando alguien pide lugar o cancela,
 * a quien reservó cuando el anfitrión confirma, rechaza o cancela.
 */
class BookingUpdatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public const REQUESTED = 'requested';

    public const CONFIRMED = 'confirmed';

    public const DECLINED = 'declined';

    public const CANCELLED_BY_GUEST = 'cancelled_by_guest';

    public const CANCELLED_BY_HOST = 'cancelled_by_host';

    public function __construct(
        public readonly Booking $booking,
        public readonly string $event,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * El aviso en Tinku se guarda al momento; el mail sale por la cola.
     *
     * @return array<string, string>
     */
    public function viaConnections(): array
    {
        return ['database' => 'sync'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->title())
            ->greeting('Hola, '.str($notifiable->name)->before(' ').'.')
            ->line($this->body())
            ->action($this->forHost() ? 'Andá a tus reservas' : 'Mirá tu reserva', $this->url())
            ->salutation('Viví el lugar con su gente.');
    }

    /**
     * @return array{title: string, body: string, url: string, icon: string}
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => $this->title(),
            'body' => $this->body(),
            'url' => $this->url(),
            'icon' => in_array($this->event, [self::CONFIRMED, self::REQUESTED], true) ? 'calendar-blank' : 'bell',
        ];
    }

    private function forHost(): bool
    {
        return in_array($this->event, [self::REQUESTED, self::CANCELLED_BY_GUEST], true);
    }

    private function url(): string
    {
        return $this->forHost() ? route('anfitrion.panel').'#reservas' : route('cuenta.reservas');
    }

    private function title(): string
    {
        $title = $this->booking->experience->title;

        return match ($this->event) {
            self::REQUESTED => "Nueva reserva para confirmar: {$title}",
            self::CONFIRMED => "Reserva confirmada: {$title}",
            self::DECLINED => "No se pudo confirmar tu reserva: {$title}",
            self::CANCELLED_BY_GUEST => "Cancelaron una reserva: {$title}",
            self::CANCELLED_BY_HOST => "El anfitrión canceló tu reserva: {$title}",
        };
    }

    private function body(): string
    {
        $booking = $this->booking;
        $when = $booking->date->localStart()->translatedFormat('l j \d\e F \a \l\a\s H:i');
        $people = plural_es($booking->guests, 'persona', 'personas');
        $guest = str($booking->user->name)->before(' ');

        return match ($this->event) {
            self::REQUESTED => "{$guest} pidió lugar para {$people} el {$when}. Confirmala dentro de las 24 horas.",
            self::CONFIRMED => "Te esperan el {$when} ({$people}). Código {$booking->code}. Recién ahora se cobra.",
            self::DECLINED => "El anfitrión no puede recibirte el {$when}. No se te cobró nada. Probá con otra fecha.",
            self::CANCELLED_BY_GUEST => "{$guest} canceló su lugar del {$when} ({$people}). Los lugares quedaron libres.",
            self::CANCELLED_BY_HOST => "Se canceló tu lugar del {$when}. Si se había cobrado, se reintegra.",
        };
    }
}
