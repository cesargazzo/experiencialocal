<?php

namespace App\Notifications;

use App\Models\Conversation;
use App\Models\User;
use App\Notifications\Concerns\MailsWhenEnabled;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Aviso de mensaje nuevo. Nunca incluye el texto: el mensaje se lee en Tinku.
 */
class NewMessageNotification extends Notification implements ShouldQueue
{
    use MailsWhenEnabled;
    use Queueable;

    public function __construct(
        public readonly Conversation $conversation,
        public readonly User $sender,
    ) {}

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->title($notifiable))
            ->greeting('Hola, '.$notifiable->first_name.'.')
            ->line($this->body($notifiable))
            ->action('Leé el mensaje', route('mensajes.show', $this->conversation))
            ->line('Por tu seguridad, el contenido de los mensajes se lee solo dentro de Tinku.')
            ->salutation('Viví el lugar con su gente.');
    }

    /**
     * @return array{title: string, body: string, url: string, icon: string}
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => $this->title($notifiable),
            'body' => $this->body($notifiable),
            'url' => route('mensajes.show', $this->conversation),
            'icon' => 'envelope-simple',
        ];
    }

    private function title(object $notifiable): string
    {
        return 'Mensaje nuevo de '.$this->sender->publicName($notifiable);
    }

    private function body(object $notifiable): string
    {
        return $this->sender->publicName($notifiable).' te escribió sobre '.$this->conversation->experience->title.'.';
    }
}
