<?php

namespace App\Models;

use App\Enums\ExperienceStatus;
use App\Jobs\NotifyInterestedUsers;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

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

    protected static function booted(): void
    {
        // Una fecha nueva en una experiencia ya publicada también es una disponibilidad.
        static::created(function (ExperienceDate $date): void {
            $experience = $date->experience;
            if ($experience && $experience->status === ExperienceStatus::Published && ! $experience->wasRecentlyCreated && $experience->published_at?->lt(now()->subMinutes(5))) {
                NotifyInterestedUsers::dispatch($experience, 'new_date')->afterCommit()->delay(now()->addMinute());
            }
        });
    }

    public function experience(): BelongsTo
    {
        return $this->belongsTo(Experience::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    /** Inicio en la hora local de la experiencia, listo para mostrar. */
    public function localStart(): Carbon
    {
        return $this->starts_at->copy()->timezone($this->experience->timezone());
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
