<?php

namespace App\Models;

use App\Enums\BookingStatus;
use Database\Factories\ConversationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Conversación privada entre un viajero y el anfitrión de una experiencia.
 * Solo la leen sus dos participantes.
 */
#[Fillable(['experience_id', 'guest_id', 'host_user_id', 'last_message_at', 'guest_last_read_id', 'host_last_read_id'])]
class Conversation extends Model
{
    /** @use HasFactory<ConversationFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'last_message_at' => 'datetime',
            'guest_last_read_id' => 'integer',
            'host_last_read_id' => 'integer',
        ];
    }

    public function experience(): BelongsTo
    {
        return $this->belongsTo(Experience::class);
    }

    public function guest(): BelongsTo
    {
        return $this->belongsTo(User::class, 'guest_id');
    }

    public function hostUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'host_user_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class)->orderBy('id');
    }

    public function latestMessage(): HasOne
    {
        return $this->hasOne(Message::class)->latestOfMany();
    }

    public function scopeFor(Builder $query, User $user): Builder
    {
        return $query->where(fn ($q) => $q->where('guest_id', $user->id)->orWhere('host_user_id', $user->id));
    }

    public function isParticipant(User $user): bool
    {
        return in_array($user->id, [$this->guest_id, $this->host_user_id], true);
    }

    public function otherParticipant(User $user): User
    {
        return $user->id === $this->guest_id ? $this->hostUser : $this->guest;
    }

    public function hasUnreadFor(User $user): bool
    {
        $lastRead = $user->id === $this->guest_id ? $this->guest_last_read_id : $this->host_last_read_id;

        return $this->messages()->where('sender_id', '!=', $user->id)->where('id', '>', $lastRead)->exists();
    }

    public function markReadBy(User $user): void
    {
        $latest = (int) $this->messages()->max('id');
        $this->forceFill([$user->id === $this->guest_id ? 'guest_last_read_id' : 'host_last_read_id' => $latest])->save();
    }

    /** Con una reserva confirmada ya se pueden compartir datos de contacto. */
    public function hasConfirmedBooking(): bool
    {
        return Booking::query()
            ->where('experience_id', $this->experience_id)
            ->where('user_id', $this->guest_id)
            ->whereIn('status', [BookingStatus::Confirmed, BookingStatus::Completed])
            ->exists();
    }

    /** Cantidad de conversaciones con mensajes sin leer para la persona. */
    public static function unreadCountFor(User $user): int
    {
        return self::query()->for($user)
            ->whereExists(fn ($q) => $q->selectRaw('1')->from('messages')
                ->whereColumn('messages.conversation_id', 'conversations.id')
                ->where('messages.sender_id', '!=', $user->id)
                ->whereRaw('messages.id > (case when conversations.guest_id = ? then conversations.guest_last_read_id else conversations.host_last_read_id end)', [$user->id]))
            ->count();
    }
}
