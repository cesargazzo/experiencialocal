<?php

namespace App\Notifications;

use App\Models\Experience;
use App\Models\ExperienceDate;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Aviso de una experiencia que coincide con los intereses de la persona.
 */
class ExperienceMatchNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly Experience $experience,
        public readonly ExperienceDate $nextDate,
        public readonly string $reason = 'published',
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

    /**
     * @return array{title: string, body: string, url: string, icon: string}
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => $this->reason === 'new_date' ? "Fechas nuevas: {$this->experience->title}" : "Nueva en Tinku: {$this->experience->title}",
            'body' => 'Coincide con tus intereses. Próxima fecha: '.$this->nextDate->localStart()->translatedFormat('D j M · H:i').'.',
            'url' => route('experiencias.show', $this->experience),
            'icon' => 'sparkle',
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $host = $this->experience->host->user->first_name;
        $when = $this->nextDate->localStart()->translatedFormat('l j \d\e F \a \l\a\s H:i');
        $intro = $this->reason === 'new_date'
            ? "Hay fechas nuevas para {$this->experience->title}, con {$host} en {$this->experience->placeLabel()}."
            : "{$host} publicó {$this->experience->title}, en {$this->experience->placeLabel()}.";

        return (new MailMessage)
            ->subject($this->reason === 'new_date' ? "Fechas nuevas: {$this->experience->title}" : "Nueva en Tinku: {$this->experience->title}")
            ->greeting('Hola, '.str($notifiable->name)->before(' ').'.')
            ->line($intro)
            ->line("Próxima fecha: {$when}. Desde ".money($this->experience->price).' por persona.')
            ->action('Mirá la experiencia', route('experiencias.show', $this->experience))
            ->line('Te avisamos porque coincide con tus intereses. Podés cambiarlos o dejar de recibir avisos en '.route('cuenta.intereses').'.')
            ->salutation('Viví el lugar con su gente.');
    }
}
