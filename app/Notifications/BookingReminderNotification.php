<?php

namespace App\Notifications;

use App\Enums\BookingReminder;
use App\Models\Booking;
use App\Notifications\Concerns\MailsWhenEnabled;
use App\Support\CalendarInvite;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Recordatorio de una reserva: una semana antes, un día antes o el mismo día.
 * A quien reservó le lleva el punto de encuentro y cómo cancelar; al anfitrión,
 * a quién recibe. Sale en el idioma de cada persona y el mail lleva el .ics.
 */
class BookingReminderNotification extends Notification implements ShouldQueue
{
    use MailsWhenEnabled;
    use Queueable;

    public function __construct(
        public readonly Booking $booking,
        public readonly BookingReminder $reminder = BookingReminder::Day,
        public readonly bool $forHost = false,
    ) {}

    public function toMail(object $notifiable): MailMessage
    {
        $experience = $this->booking->experience;
        $mail = (new MailMessage)
            ->subject($this->title())
            ->greeting(__('Hola, :name.', ['name' => $notifiable->first_name]))
            ->line($this->body());

        if (! $this->forHost && $experience->meeting_address) {
            $mail->line(__('Punto de encuentro: :address.', ['address' => $experience->meeting_address]));
            if ($url = $experience->directionsUrl()) {
                $mail->action(__('Cómo llegar'), $url);
            }
        } else {
            $mail->action($this->forHost ? __('Mirá tus reservas') : __('Mirá tu reserva'), $this->url());
        }

        if (! $this->forHost) {
            $mail->line(__('¿No podés ir? Cancelá desde Tus reservas así otra persona puede tomar tu lugar.'))
                ->attachData(CalendarInvite::forBooking($this->booking), 'tinku-'.$this->booking->code.'.ics', ['mime' => 'text/calendar; charset=UTF-8']);
        }

        return $mail
            ->line($this->forHost ? __('Si surge algo, escribile desde Mensajes.') : __('Si surge algo, escribile al anfitrión desde Mensajes.'))
            ->salutation(__('Viví el lugar con su gente.'));
    }

    /**
     * @return array{title: string, body: string, url: string, icon: string}
     */
    public function toArray(object $notifiable): array
    {
        return ['title' => $this->title(), 'body' => $this->body(), 'url' => $this->url(), 'icon' => 'calendar-blank'];
    }

    /** Día y hora en el idioma de quien lo recibe: "jueves, 8 de octubre de 2026 20:30". */
    private function when(): string
    {
        return $this->booking->date->localStart()->locale(app()->getLocale())->isoFormat('LLLL');
    }

    public function title(): string
    {
        $title = $this->booking->experience->title;

        return $this->forHost
            ? match ($this->reminder) {
                BookingReminder::Today => __('Hoy recibís: :title', ['title' => $title]),
                default => __('Mañana recibís: :title', ['title' => $title]),
            }
        : match ($this->reminder) {
            BookingReminder::Week => __('En una semana: :title', ['title' => $title]),
            BookingReminder::Day => __('Mañana es tu experiencia: :title', ['title' => $title]),
            BookingReminder::Today => __('Hoy es tu experiencia: :title', ['title' => $title]),
        };
    }

    public function body(): string
    {
        $people = plural_es($this->booking->guests, __('persona'), __('personas'));

        return $this->forHost
            ? __(':guest viene el :when (:people). Código :code.', ['guest' => $this->booking->user->first_name, 'when' => $this->when(), 'people' => $people, 'code' => $this->booking->code])
            : __('Te espera :host el :when (:people). Código :code.', ['host' => $this->booking->experience->host->user->first_name, 'when' => $this->when(), 'people' => $people, 'code' => $this->booking->code]);
    }

    private function url(): string
    {
        return $this->forHost ? route('anfitrion.panel').'#reservas' : route('cuenta.reservas');
    }
}
