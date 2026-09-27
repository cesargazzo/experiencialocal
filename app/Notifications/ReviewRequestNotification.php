<?php

namespace App\Notifications;

use App\Models\Booking;
use App\Notifications\Concerns\MailsWhenEnabled;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Después de la experiencia: "¿Cómo te fue?". También sirve para avisarle al
 * anfitrión que le dejaron una opinión.
 */
class ReviewRequestNotification extends Notification implements ShouldQueue
{
    use MailsWhenEnabled;
    use Queueable;

    public function __construct(
        public readonly Booking $booking,
        public readonly bool $forHost = false,
    ) {}

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->title())
            ->greeting('Hola, '.$notifiable->first_name.'.')
            ->line($this->body())
            ->action($this->forHost ? 'Leé la opinión' : 'Dejá tu opinión', $this->url())
            ->salutation('Viví el lugar con su gente.');
    }

    /**
     * @return array{title: string, body: string, url: string, icon: string}
     */
    public function toArray(object $notifiable): array
    {
        return ['title' => $this->title(), 'body' => $this->body(), 'url' => $this->url(), 'icon' => 'star'];
    }

    private function title(): string
    {
        $host = $this->booking->experience->host->user->first_name;

        return $this->forHost
            ? "Te dejaron una opinión: {$this->booking->experience->title}"
            : "¿Cómo te fue con {$host}?";
    }

    private function body(): string
    {
        return $this->forHost
            ? $this->booking->user->first_name.' contó cómo le fue. Podés responderle desde tu espacio de anfitrión.'
            : "Contanos cómo fue {$this->booking->experience->title}. Tu opinión ayuda a otras personas a elegir y al anfitrión a mejorar.";
    }

    private function url(): string
    {
        return $this->forHost ? route('anfitrion.panel').'#opiniones' : route('cuenta.reservas').'#opinar-'.$this->booking->id;
    }
}
