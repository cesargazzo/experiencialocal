<?php

namespace App\Livewire;

use App\Jobs\ModerateContent;
use App\Models\Conversation;
use App\Models\Message;
use App\Notifications\NewMessageNotification;
use App\Services\SecurityLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Component;

/**
 * Los mensajes de una conversación y el cuadro para escribir. Se actualiza solo
 * cada tanto para ver las respuestas sin recargar.
 */
class ConversationThread extends Component
{
    /** Mensajes por hora que puede mandar una cuenta, para frenar abusos. */
    public const HOURLY_LIMIT = 40;

    public Conversation $conversation;

    public string $body = '';

    public ?int $reportingId = null;

    public string $reportReason = '';

    /** Aviso para quien escribe, visible sin recargar. */
    public ?string $notice = null;

    public function mount(Conversation $conversation): void
    {
        $this->authorize('view', $conversation);
        $this->conversation = $conversation;
        $conversation->markReadBy(auth()->user());
    }

    public function send(): void
    {
        $user = auth()->user();
        $this->authorize('send', $this->conversation);
        $this->validate(['body' => ['required', 'string', 'max:2000']], ['body.required' => __('Escribí tu mensaje.'), 'body.max' => __('El mensaje puede tener hasta 2000 caracteres.')]);

        $key = 'mensajes:'.$user->id;
        if (RateLimiter::tooManyAttempts($key, self::HOURLY_LIMIT)) {
            $this->addError('body', __('Mandaste muchos mensajes seguidos. Probá de nuevo en un rato.'));

            return;
        }
        RateLimiter::hit($key, 3600);

        [$text, $redacted] = $this->conversation->hasConfirmedBooking()
            ? [trim($this->body), false]
            : Message::redactContactDetails(trim($this->body));

        $recipient = $this->conversation->otherParticipant($user);
        $recipientHadUnread = $this->conversation->hasUnreadFor($recipient);

        DB::transaction(function () use ($user, $text, $redacted): void {
            $message = $this->conversation->messages()->create(['sender_id' => $user->id, 'body' => $text, 'contact_redacted' => $redacted]);
            ModerateContent::message($message);
            $this->conversation->forceFill(['last_message_at' => now()])->save();
            $this->conversation->markReadBy($user);
        });

        // Un aviso por tanda: si ya tenía mensajes sin leer de esta conversación, no se repite.
        if (! $recipientHadUnread) {
            $recipient->notify(new NewMessageNotification($this->conversation, $user));
        }

        $this->reset('body');
        $this->notice = $redacted ? __('Ocultamos datos de contacto de tu mensaje. Cuando la reserva esté confirmada vas a poder compartirlos.') : null;
    }

    /** Se llama cada tanto desde la vista: marca como leído lo que llegó. */
    public function refreshThread(): void
    {
        $this->conversation->markReadBy(auth()->user());
    }

    public function startReport(int $messageId): void
    {
        $this->reportingId = $messageId;
        $this->reportReason = '';
    }

    public function report(SecurityLog $securityLog): void
    {
        $this->authorize('view', $this->conversation);
        $this->validate(['reportReason' => ['required', 'string', 'min:5', 'max:300']], ['reportReason.required' => __('Contanos qué pasó.'), 'reportReason.min' => __('Contanos un poco más.')]);

        $message = $this->conversation->messages()->whereKey($this->reportingId)->where('sender_id', '!=', auth()->id())->firstOrFail();
        $message->forceFill(['reported_at' => now(), 'reported_by' => auth()->id(), 'report_reason' => $this->reportReason])->save();
        $securityLog->record('message.reported', auth()->user(), ['message_id' => $message->id, 'conversation_id' => $this->conversation->id], null, 'warning');

        $this->reset('reportingId', 'reportReason');
        $this->notice = __('Gracias. El equipo de Tinku va a revisar la denuncia.');
    }

    public function render()
    {
        return view('livewire.conversation-thread', [
            'messages' => $this->conversation->messages()->with('sender.avatar')->get(),
            'me' => auth()->user(),
            'other' => $this->conversation->otherParticipant(auth()->user()),
            'canShareContact' => $this->conversation->hasConfirmedBooking(),
        ]);
    }
}
