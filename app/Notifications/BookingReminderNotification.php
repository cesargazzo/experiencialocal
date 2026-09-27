<?php

namespace App\Notifications;

use App\Models\Booking;
use App\Notifications\Concerns\MailsWhenEnabled;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Recordatorio del día anterior. A quien reservó le lleva el punto de encuentro;
 * al anfitrión, a quién recibe y cuántas personas son.
 */
class BookingReminderNotification extends Notification implements ShouldQueue
{
    use MailsWhenEnabled;
    use Queueable;

    public function __construct(
        public readonly Booking $booking,
        public readonly bool $forHost = false,
    ) {}

    public function toMail(object $notifiable): MailMessage
    {
        $experience = $this->booking->experience;
        $mail = (new MailMessage)
            ->subject($this->title())
            ->greeting('Hola, '.$notifiable->first_name.'.')
            ->line($this->body());

        if (! $this->forHost && $experience->meeting_address) {
            $mail->line('Punto de encuentro: '.$experience->meeting_address.'.');
            if ($url = $experience->directionsUrl()) {
                $mail->action('Cómo llegar', $url);
            }
        } else {
            $mail->action($this->forHost ? 'Mirá tus reservas' : 'Mirá tu reserva', $this->url());
        }

        return $mail
            ->line($this->forHost ? 'Si surge algo, escribile desde Mensajes.' : 'Si surge algo, escribile al anfitrión desde Mensajes.')
            ->salutation('Viví el lugar con su gente.');
    }

    /**
     * @return array{title: string, body: string, url: string, icon: string}
     */
    public function toArray(object $notifiable): array
    {
        return ['title' => $this->title(), 'body' => $this->body(), 'url' => $this->url(), 'icon' => 'calendar-blank'];
    }

    private function when(): string
    {
        return $this->booking->date->localStart()->translatedFormat('l j \d\e F \a \l\a\s H:i');
    }

    private function title(): string
    {
        return ($this->forHost ? 'Mañana recibís: ' : 'Mañana es tu experiencia: ').$this->booking->experience->title;
    }

    private function body(): string
    {
        $people = plural_es($this->booking->guests, 'persona', 'personas');

        return $this->forHost
            ? "{$this->booking->user->first_name} viene el {$this->when()} ({$people}). Código {$this->booking->code}."
            : "Te espera {$this->booking->experience->host->user->first_name} el {$this->when()} ({$people}). Código {$this->booking->code}.";
    }

    private function url(): string
    {
        return $this->forHost ? route('anfitrion.panel').'#reservas' : route('cuenta.reservas');
    }
}
