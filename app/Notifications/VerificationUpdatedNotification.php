<?php

namespace App\Notifications;

use App\Enums\VerificationLevel;
use App\Enums\VerificationType;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Novedades de la verificación de identidad: subió de nivel, o una
 * verificación fue rechazada o revocada por un administrador.
 */
class VerificationUpdatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly VerificationLevel $level,
        public readonly ?VerificationType $rejectedType = null,
        public readonly ?string $reason = null,
    ) {}

    public static function levelReached(VerificationLevel $level): self
    {
        return new self($level);
    }

    public static function rejected(VerificationLevel $level, VerificationType $type, string $reason): self
    {
        return new self($level, $type, $reason);
    }

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
            ->action('Mirá tu verificación', route('verificacion'))
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
            'url' => route('verificacion'),
            'icon' => $this->rejectedType ? 'bell' : 'seal-check',
        ];
    }

    private function title(): string
    {
        return $this->rejectedType
            ? 'Revisá tu verificación'
            : "Tu cuenta está validada: {$this->level->label()}";
    }

    private function body(): string
    {
        if ($this->rejectedType) {
            return "No pudimos aprobar tu verificación ({$this->rejectedType->label()}). Motivo: {$this->reason}. Tu cuenta quedó en: {$this->level->label()}.";
        }

        return match ($this->level) {
            VerificationLevel::Contact => 'Confirmaste tus datos de contacto. Para reservar, validá tu documento.',
            VerificationLevel::Document => 'Validamos tu documento. Ya podés reservar y ofrecer experiencias.',
            VerificationLevel::Residence => 'Validamos tu identidad y tu domicilio. Tenés el nivel más alto de Tinku.',
            VerificationLevel::None => 'Tu verificación cambió.',
        };
    }
}
