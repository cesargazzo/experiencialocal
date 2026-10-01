<?php

namespace App\Notifications;

use App\Models\AdvertiserInquiry;
use App\Notifications\Concerns\MailsWhenEnabled;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** Aviso al equipo: una marca quiere anunciar. */
class AdvertiserInquiryNotification extends Notification implements ShouldQueue
{
    use MailsWhenEnabled;
    use Queueable;

    public function __construct(public readonly AdvertiserInquiry $inquiry) {}

    /**
     * @return array{title: string, body: string, url: string, icon: string}
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => "Quiere anunciar: {$this->inquiry->company}",
            'body' => str($this->inquiry->message)->limit(140)->toString(),
            'url' => route('admin.anunciantes'),
            'icon' => 'sparkle',
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Quiere anunciar en Tinku: {$this->inquiry->company}")
            ->line("{$this->inquiry->name} ({$this->inquiry->company}) escribió desde el mediakit.")
            ->line(str($this->inquiry->message)->limit(400)->toString())
            ->action('Mirá la consulta', route('admin.anunciantes'));
    }
}
