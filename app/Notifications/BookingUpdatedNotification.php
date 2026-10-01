<?php

namespace App\Notifications;

use App\Models\Booking;
use App\Notifications\Concerns\MailsWhenEnabled;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Novedades de una reserva: al anfitrión cuando alguien pide lugar o cancela,
 * a quien reservó cuando el anfitrión confirma, rechaza, cancela o marca que no
 * se presentó (y si Tinku revierte esa marca).
 */
class BookingUpdatedNotification extends Notification implements ShouldQueue
{
    use MailsWhenEnabled;
    use Queueable;

    public const REQUESTED = 'requested';

    public const CONFIRMED = 'confirmed';

    public const DECLINED = 'declined';

    public const CANCELLED_BY_GUEST = 'cancelled_by_guest';

    public const CANCELLED_BY_HOST = 'cancelled_by_host';

    public const NO_SHOW = 'no_show';

    public const NO_SHOW_REVERTED = 'no_show_reverted';

    public function __construct(
        public readonly Booking $booking,
        public readonly string $event,
    ) {}

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->title())
            ->greeting(__('Hola, :name.', ['name' => str($notifiable->name)->before(' ')]))
            ->line($this->body())
            ->action($this->forHost() ? __('Andá a tus reservas') : __('Mirá tu reserva'), $this->url())
            ->salutation(__('Viví el lugar con su gente.'));
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
            self::REQUESTED => __('Nueva reserva para confirmar: :title', ['title' => $title]),
            self::CONFIRMED => __('Reserva confirmada: :title', ['title' => $title]),
            self::DECLINED => __('No se pudo confirmar tu reserva: :title', ['title' => $title]),
            self::CANCELLED_BY_GUEST => __('Cancelaron una reserva: :title', ['title' => $title]),
            self::CANCELLED_BY_HOST => __('El anfitrión canceló tu reserva: :title', ['title' => $title]),
            self::NO_SHOW => __('El anfitrión marcó que no fuiste: :title', ['title' => $title]),
            self::NO_SHOW_REVERTED => __('Corregimos tu reserva: :title', ['title' => $title]),
        };
    }

    private function body(): string
    {
        $booking = $this->booking;
        $when = $booking->date->localStart()->isoFormat('LLLL');
        $people = plural_es($booking->guests, __('persona'), __('personas'));
        $guest = str($booking->user->name)->before(' ');

        return match ($this->event) {
            self::REQUESTED => __(':guest pidió lugar para :people el :when. Confirmala dentro de las 24 horas.', ['guest' => $guest, 'people' => $people, 'when' => $when]),
            self::CONFIRMED => __('Te esperan el :when (:people). Código :code. Recién ahora se cobra.', ['when' => $when, 'people' => $people, 'code' => $booking->code]),
            self::DECLINED => __('El anfitrión no puede recibirte el :when. No se te cobró nada. Probá con otra fecha.', ['when' => $when]),
            self::CANCELLED_BY_GUEST => __(':guest canceló su lugar del :when (:people). Los lugares quedaron libres.', ['guest' => $guest, 'when' => $when, 'people' => $people]),
            self::CANCELLED_BY_HOST => __('Se canceló tu lugar del :when. Si se había cobrado, se reintegra.', ['when' => $when]),
            self::NO_SHOW => __('Según el anfitrión, no te presentaste el :when. No hay devolución y, si se repite, no vas a poder reservar por un tiempo. Si fuiste o hubo un problema, escribinos desde Ayuda y lo revisamos.', ['when' => $when]),
            self::NO_SHOW_REVERTED => __('Revisamos lo que pasó el :when y la reserva vuelve a figurar como realizada. Ya podés dejar tu opinión.', ['when' => $when]),
        };
    }
}
