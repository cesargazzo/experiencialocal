<?php

namespace App\Models;

use App\Enums\BookingStatus;
use App\Enums\CancellationPolicy;
use App\Enums\DietaryOption;
use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\AsEnumCollection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

#[Fillable([
    'code', 'experience_date_id', 'experience_id', 'user_id', 'guests', 'unit_price', 'subtotal', 'service_fee_rate',
    'service_fee', 'total', 'commission_rate', 'commission_amount', 'host_payout', 'currency', 'status', 'guest_note', 'dietary_needs', 'food_allergies',
    'payment_provider', 'payment_reference', 'confirmed_at', 'declined_at', 'cancelled_at', 'paid_at', 'completed_at', 'no_show_at', 'refunded_at', 'reminders_sent',
    'cancellation_policy', 'refund_percent',
])]
class Booking extends Model
{
    use Auditable;
    use HasFactory;

    /** @var list<string> Salud y religión: datos sensibles, no se guardan en la auditoría. */
    protected array $auditMasked = ['dietary_needs', 'food_allergies'];

    protected function casts(): array
    {
        return [
            'status' => BookingStatus::class,
            'reminders_sent' => 'array',
            'cancellation_policy' => CancellationPolicy::class,
            'refund_percent' => 'integer',
            'dietary_needs' => AsEnumCollection::of(DietaryOption::class),
            'unit_price' => 'decimal:2',
            'subtotal' => 'decimal:2',
            'service_fee_rate' => 'decimal:4',
            'service_fee' => 'decimal:2',
            'total' => 'decimal:2',
            'commission_rate' => 'decimal:4',
            'commission_amount' => 'decimal:2',
            'host_payout' => 'decimal:2',
            'confirmed_at' => 'datetime',
            'declined_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'paid_at' => 'datetime',
            'completed_at' => 'datetime',
            'no_show_at' => 'datetime',
            'refunded_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Booking $booking) {
            $booking->code ??= 'TK-'.Str::upper(Str::random(6));
        });
    }

    public function date(): BelongsTo
    {
        return $this->belongsTo(ExperienceDate::class, 'experience_date_id');
    }

    public function experience(): BelongsTo
    {
        return $this->belongsTo(Experience::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function review(): HasOne
    {
        return $this->hasOne(Review::class);
    }

    public function canBeReviewed(): bool
    {
        return $this->status === BookingStatus::Completed && ! $this->review()->exists();
    }

    /** La política que aceptó al reservar (las reservas anteriores a las políticas quedaron como moderadas). */
    public function cancellationPolicy(): CancellationPolicy
    {
        return $this->cancellation_policy ?? CancellationPolicy::Moderate;
    }

    /** Qué porcentaje se le devolvería a quien reservó si cancela ahora. Mientras no está confirmada no se cobró nada. */
    public function guestRefundPercentNow(): int
    {
        if ($this->status !== BookingStatus::Confirmed) {
            return 100;
        }

        return $this->cancellationPolicy()->refundPercent(max(0, now()->diffInHours($this->date->starts_at, false)));
    }

    /** El anfitrión puede marcar que no vino desde que empieza la experiencia y por unas horas, si no dejó opinión. */
    public function canBeMarkedNoShow(): bool
    {
        $startsAt = $this->date->starts_at;

        return in_array($this->status, [BookingStatus::Confirmed, BookingStatus::Completed], true)
            && $startsAt->isPast()
            && now()->lte($startsAt->copy()->addHours(config('tinku.no_shows.mark_window_hours')))
            && ! $this->review()->exists();
    }
}
