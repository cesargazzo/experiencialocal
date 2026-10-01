<?php

namespace App\Models;

use App\Models\Concerns\HasModerationReviews;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Mensaje de una conversación. El texto se guarda cifrado con la clave de la
 * aplicación y no pasa por la auditoría.
 */
#[Fillable(['conversation_id', 'sender_id', 'body', 'contact_redacted', 'reported_at', 'reported_by', 'report_reason', 'hidden_at'])]
class Message extends Model
{
    use HasModerationReviews;

    public const UPDATED_AT = null;

    public const REDACTED = '[dato de contacto oculto]';

    protected function casts(): array
    {
        return [
            'body' => 'encrypted',
            'contact_redacted' => 'boolean',
            'reported_at' => 'datetime',
            'hidden_at' => 'datetime',
        ];
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    /**
     * Oculta mails, teléfonos, enlaces y usuarios de redes. Antes de una reserva
     * confirmada, así nadie arregla por fuera de Tinku (y se cuida a las dos partes).
     *
     * @return array{0: string, 1: bool} Texto y si se ocultó algo.
     */
    public static function redactContactDetails(string $text): array
    {
        $patterns = [
            '/[\w.+-]+@[\w-]+(\.[\w-]+)+/u',                               // mails
            '/\b(https?:\/\/|www\.)\S+/iu',                                  // enlaces
            '/\b[\w-]+\.(com|ar|net|org|me|io|app|link|ly)(\/\S*)?\b/iu',    // dominios sueltos
            '/(?<!\w)@[\w.]{3,}/u',                                          // @usuario
            '/(\+?\d[\d\s().-]{6,}\d)/u',                                    // teléfonos
        ];

        $redacted = preg_replace($patterns, self::REDACTED, $text) ?? $text;

        return [$redacted, $redacted !== $text];
    }
}
