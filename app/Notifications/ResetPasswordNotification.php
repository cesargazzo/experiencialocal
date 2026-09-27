<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Enlace para elegir una contraseña nueva. Se envía por Mandrill.
 */
class ResetPasswordNotification extends Notification
{
    use Queueable;

    public function __construct(public readonly string $token) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $minutes = config('auth.passwords.'.config('auth.defaults.passwords').'.expire');
        $url = route('password.reset', ['token' => $this->token, 'email' => $notifiable->getEmailForPasswordReset()]);

        return (new MailMessage)
            ->subject('Elegí una contraseña nueva para Tinku')
            ->greeting('Hola, '.str($notifiable->name)->before(' ').'.')
            ->line('Recibimos un pedido para cambiar la contraseña de tu cuenta de Tinku.')
            ->action('Elegí una contraseña nueva', $url)
            ->line("El enlace vence en {$minutes} minutos y sirve una sola vez.")
            ->line('Si no lo pediste, ignorá este mensaje: tu contraseña sigue igual.')
            ->salutation('Nos vemos en Tinku.');
    }
}
