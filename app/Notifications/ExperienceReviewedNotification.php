<?php

namespace App\Notifications;

use App\Enums\ExperienceStatus;
use App\Models\Experience;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Resultado de la revisión de una experiencia, para su anfitrión.
 */
class ExperienceReviewedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly Experience $experience,
        public readonly bool $approved,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)->greeting('Hola, '.str($notifiable->name)->before(' ').'.');

        if (! $this->approved) {
            return $mail
                ->subject("Revisá tu experiencia: {$this->experience->title}")
                ->line("Revisamos {$this->experience->title} y todavía no la podemos publicar.")
                ->line("Motivo: {$this->experience->rejection_reason}")
                ->line('Respondé este mail y la vemos juntos.')
                ->salutation('Viví el lugar con su gente.');
        }

        $published = $this->experience->status === ExperienceStatus::Published;

        return $mail
            ->subject($published ? "Ya está publicada: {$this->experience->title}" : "Aprobamos tu experiencia: {$this->experience->title}")
            ->line($published
                ? "Aprobamos {$this->experience->title} y ya está publicada. Cuando alguien reserve, te avisamos."
                : "Aprobamos {$this->experience->title}. Se publica sola cuando valides tu domicilio.")
            ->action('Mirá tu experiencia', route('experiencias.show', $this->experience))
            ->salutation('Viví el lugar con su gente.');
    }
}
