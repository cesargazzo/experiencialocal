<?php

namespace App\Models;

use App\Enums\DietaryOption;
use App\Enums\ExperienceStatus;
use App\Jobs\NotifyInterestedUsers;
use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\AsEnumCollection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

#[Fillable([
    'host_profile_id', 'category_id', 'title', 'slug', 'type_label', 'summary', 'description', 'city', 'province_id',
    'country_code', 'price', 'currency', 'duration_minutes', 'max_guests', 'includes', 'cover_image_url', 'status', 'published_at',
    'dietary_options', 'approved_at', 'approved_by', 'rejection_reason', 'paused_reason',
])]
class Experience extends Model
{
    use Auditable;
    use HasFactory;

    protected function casts(): array
    {
        return [
            'status' => ExperienceStatus::class,
            'price' => 'decimal:2',
            'includes' => 'array',
            'rating_avg' => 'decimal:2',
            'published_at' => 'datetime',
            'dietary_options' => AsEnumCollection::of(DietaryOption::class),
            'approved_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        // Al publicarse, se avisa a quienes tienen intereses que coinciden.
        static::saved(function (Experience $experience): void {
            if ($experience->status === ExperienceStatus::Published && ($experience->wasRecentlyCreated || $experience->wasChanged('status'))) {
                NotifyInterestedUsers::dispatch($experience, 'published')->afterCommit()->delay(now()->addMinute());
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function host(): BelongsTo
    {
        return $this->belongsTo(HostProfile::class, 'host_profile_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function province(): BelongsTo
    {
        return $this->belongsTo(Province::class);
    }

    public function cover(): MorphOne
    {
        return $this->morphOne(Media::class, 'mediable')->ofMany(
            ['id' => 'max'],
            fn ($query) => $query->where('collection', 'cover')->whereIn('status', Media::VISIBLE_STATUSES),
        );
    }

    /** Última foto subida, esté lista o no: sirve para mostrar si se está procesando o falló. */
    public function latestCoverUpload(): MorphOne
    {
        return $this->morphOne(Media::class, 'mediable')->ofMany(['id' => 'max'], fn ($query) => $query->where('collection', 'cover'));
    }

    /**
     * Foto subida en la versión pedida (card, hero, og). Si no hay, la URL de
     * portada cargada a mano (datos demo).
     */
    public function coverUrl(string $variant = 'card'): ?string
    {
        return $this->cover?->url($variant) ?? $this->cover_image_url;
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function dates(): HasMany
    {
        return $this->hasMany(ExperienceDate::class)->orderBy('starts_at');
    }

    public function upcomingDates(): HasMany
    {
        return $this->dates()->where('status', 'open')->where('starts_at', '>', now());
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class)->whereNotNull('published_at')->latest('published_at');
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            ExperienceStatus::Draft => $this->rejection_reason ? 'Para corregir' : 'Borrador',
            ExperienceStatus::InReview => $this->approved_at ? 'Aprobada, falta el domicilio' : 'En revisión',
            ExperienceStatus::Published => 'Publicada',
            ExperienceStatus::Paused => 'Pausada',
            ExperienceStatus::Archived => 'Archivada',
        };
    }

    public function statusBadgeClass(): string
    {
        return match ($this->status) {
            ExperienceStatus::Published => 'badge--ok',
            ExperienceStatus::InReview => 'badge--espera',
            ExperienceStatus::Paused => 'badge--error',
            ExperienceStatus::Draft => $this->rejection_reason ? 'badge--error' : 'badge--nivel-1',
            ExperienceStatus::Archived => 'badge--nivel-1',
        };
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', ExperienceStatus::Published);
    }

    /** Zona horaria del lugar: las fechas se guardan en UTC y se muestran en la hora local. */
    public function timezone(): string
    {
        return $this->province?->timezone ?? config('tinku.timezone');
    }

    /** "Chilecito, La Rioja". */
    public function placeLabel(): string
    {
        return $this->province ? "{$this->city}, {$this->province->name}" : $this->city;
    }

    public function durationLabel(): string
    {
        $h = intdiv($this->duration_minutes, 60);
        $m = $this->duration_minutes % 60;

        return $m ? sprintf('%d h %02d', $h, $m) : sprintf('%d h', $h);
    }

    /** Recalcula el promedio a partir de las opiniones publicadas. */
    public function refreshRating(): void
    {
        $stats = $this->reviews()->reorder()->selectRaw('count(*) as c, coalesce(avg(rating), 0) as a')->first();
        $this->forceFill(['reviews_count' => (int) $stats->c, 'rating_avg' => round((float) $stats->a, 2)])->save();
    }
}
