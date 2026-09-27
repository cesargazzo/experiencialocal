<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Código de seis dígitos para confirmar el email. Se envía por Mandrill.
 */
class VerificationCodeNotification extends Notification
{
    use Queueable;

    public function __construct(public readonly string $code, public readonly int $validForMinutes = 30) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Tu código de Tinku: {$this->code}")
            ->greeting('Hola, '.str($notifiable->name)->before(' ').'.')
            ->line('Este es tu código para confirmar tu email en Tinku:')
            ->line("## {$this->code}")
            ->line("Vence en {$this->validForMinutes} minutos. Si no lo pediste, ignorá este mensaje.")
            ->action('Confirmá tu email', route('verificacion'))
            ->salutation('Nos vemos en Tinku.');
    }
}
