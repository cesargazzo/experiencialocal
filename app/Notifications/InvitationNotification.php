<?php

namespace App\Notifications;

use App\Models\Invitation;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Invitación por email. Se manda a una dirección, sin que haga falta una cuenta.
 */
class InvitationNotification extends Notification
{
    use Queueable;

    public function __construct(public readonly Invitation $invitation, public readonly string $url) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        // Quien recibe la invitación todavía no validó su identidad: solo ve el nombre de pila.
        $inviter = $this->invitation->inviter->first_name;
        $greeting = $this->invitation->name ? 'Hola, '.str($this->invitation->name)->before(' ').'.' : 'Hola.';

        return (new MailMessage)
            ->subject("{$inviter} te invita a Tinku")
            ->greeting($greeting)
            ->line("{$inviter} te invita a sumarte a Tinku, donde reservás experiencias con la gente que vive en cada lugar: una comida en su casa, una clase de cocina, un paseo.")
            ->action('Sumate a Tinku', $this->url)
            ->line('La invitación vence el '.$this->invitation->expires_at->timezone(config('tinku.timezone'))->translatedFormat('j \d\e F').' y sirve una sola vez.')
            ->salutation('Viví el lugar con su gente.');
    }
}
