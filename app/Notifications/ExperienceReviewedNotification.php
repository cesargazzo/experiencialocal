<?php

namespace App\Notifications;

use App\Enums\ExperienceStatus;
use App\Models\Experience;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Lo que decidió la administración sobre una experiencia, para su anfitrión.
 */
class ExperienceReviewedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public const APPROVED = 'approved';

    public const REJECTED = 'rejected';

    public const PAUSED = 'paused';

    public const RESUMED = 'resumed';

    public function __construct(
        public readonly Experience $experience,
        public readonly string $outcome,
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
        $title = $this->experience->title;
        $mail = (new MailMessage)
            ->subject($this->subject())
            ->greeting('Hola, '.str($notifiable->name)->before(' ').'.');

        $mail = match ($this->outcome) {
            self::REJECTED => $mail
                ->line("Revisamos {$title} y todavía no la podemos publicar.")
                ->line("Motivo: {$this->experience->rejection_reason}")
                ->line('Corregila desde tu espacio de anfitrión y la revisamos de nuevo.'),
            self::PAUSED => $mail
                ->line("Pausamos {$title}: por ahora no se muestra ni recibe reservas nuevas.")
                ->line("Motivo: {$this->experience->paused_reason}")
                ->line('Las reservas que ya tenías siguen en pie. Respondé este mail si querés conversarlo.'),
            default => $mail->line($this->summary()),
        };

        return $mail
            ->action('Andá a tu espacio de anfitrión', route('anfitrion.panel'))
            ->salutation('Viví el lugar con su gente.');
    }

    public function subject(): string
    {
        $title = $this->experience->title;

        return match ($this->outcome) {
            self::REJECTED => "Revisá tu experiencia: {$title}",
            self::PAUSED => "Pausamos tu experiencia: {$title}",
            self::RESUMED => "Reactivamos tu experiencia: {$title}",
            default => $this->isPublished() ? "Ya está publicada: {$title}" : "Aprobamos tu experiencia: {$title}",
        };
    }

    /** Una línea que resume la novedad. */
    public function summary(): string
    {
        $title = $this->experience->title;

        return match ($this->outcome) {
            self::REJECTED => "No pudimos publicar {$title}. Motivo: {$this->experience->rejection_reason}",
            self::PAUSED => "Pausamos {$title}. Motivo: {$this->experience->paused_reason}",
            self::RESUMED => $this->isPublished() ? "Reactivamos {$title} y ya se ve de nuevo." : "Reactivamos {$title}. Se publica cuando termine la revisión.",
            default => $this->isPublished()
                ? "Aprobamos {$title} y ya está publicada. Cuando alguien reserve, te avisamos."
                : "Aprobamos {$title}. Se publica sola cuando valides tu domicilio.",
        };
    }

    private function isPublished(): bool
    {
        return $this->experience->status === ExperienceStatus::Published;
    }
}
