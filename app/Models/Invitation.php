<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * Invitación a sumarse a Tinku, por email o con un enlace para compartir.
 * Sirve una sola vez y vence.
 */
#[Fillable(['inviter_id', 'channel', 'name', 'email', 'token_hash', 'expires_at', 'accepted_by', 'accepted_at'])]
class Invitation extends Model
{
    use Auditable;

    /** Token en claro: solo existe en memoria justo después de crearla. */
    public ?string $plainToken = null;

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'accepted_at' => 'datetime',
        ];
    }

    /**
     * @param  array{channel: string, name?: ?string, email?: ?string}  $attributes
     */
    public static function issue(User $inviter, array $attributes): self
    {
        $token = Str::random(40);

        $invitation = $inviter->invitations()->create([
            ...$attributes,
            'email' => isset($attributes['email']) ? Str::lower(trim($attributes['email'])) : null,
            'token_hash' => hash('sha256', $token),
            'expires_at' => now()->addDays((int) config('tinku.invitations.valid_days')),
        ]);
        $invitation->plainToken = $token;

        return $invitation;
    }

    public static function findUsableByToken(string $token): ?self
    {
        return static::query()
            ->where('token_hash', hash('sha256', $token))
            ->whereNull('accepted_at')
            ->where('expires_at', '>', now())
            ->first();
    }

    public function inviter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'inviter_id');
    }

    public function acceptedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'accepted_by');
    }

    public function url(): string
    {
        return route('invitacion.aceptar', $this->plainToken ?? throw new \LogicException('El enlace solo se puede armar al crear la invitación.'));
    }

    public function statusLabel(): string
    {
        return match (true) {
            $this->accepted_at !== null => 'Aceptada',
            $this->expires_at->isPast() => 'Vencida',
            default => 'Pendiente',
        };
    }
}
