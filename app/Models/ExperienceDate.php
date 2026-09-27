<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['experience_id', 'starts_at', 'ends_at', 'capacity', 'booked_count', 'status'])]
class ExperienceDate extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    public function experience(): BelongsTo
    {
        return $this->belongsTo(Experience::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function seatsLeft(): int
    {
        return max(0, $this->capacity - $this->booked_count);
    }

    public function isOpen(): bool
    {
        return $this->status === 'open' && $this->starts_at->isFuture();
    }
}
