<?php

namespace App\Models;

use App\Enums\DietaryOption;
use App\Enums\Difficulty;
use App\Enums\ExperienceFeature;
use App\Enums\ExperienceStatus;
use App\Jobs\ModerateContent;
use App\Jobs\NotifyInterestedUsers;
use App\Models\Concerns\Auditable;
use App\Models\Concerns\HasModerationReviews;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\AsEnumCollection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

#[Fillable([
    'host_profile_id', 'category_id', 'title', 'slug', 'type_label', 'summary', 'description', 'city', 'province_id',
    'meeting_address', 'latitude', 'longitude', 'address_normalized_at',
    'country_code', 'price', 'currency', 'duration_minutes', 'max_guests', 'includes', 'cover_image_url', 'status', 'published_at',
    'dietary_options', 'difficulty', 'what_to_bring', 'min_age', 'features', 'approved_at', 'approved_by', 'rejection_reason', 'paused_reason',
])]
class Experience extends Model
{
    use Auditable;
    use HasFactory;
    use HasModerationReviews;

    protected function casts(): array
    {
        return [
            'status' => ExperienceStatus::class,
            'price' => 'decimal:2',
            'includes' => 'array',
            'rating_avg' => 'decimal:2',
            'published_at' => 'datetime',
            'dietary_options' => AsEnumCollection::of(DietaryOption::class),
            'difficulty' => Difficulty::class,
            'features' => AsEnumCollection::of(ExperienceFeature::class),
            'approved_at' => 'datetime',
            'latitude' => 'float',
            'longitude' => 'float',
            'address_normalized_at' => 'datetime',
        ];
    }

    /** @var list<string> Textos que revisa la IA. */
    public const MODERATED_FIELDS = ['title', 'type_label', 'summary', 'description', 'what_to_bring'];

    protected static function booted(): void
    {
        // Al publicarse, se avisa a quienes tienen intereses que coinciden.
        static::saved(function (Experience $experience): void {
            // wasRecentlyCreated sigue en true en los guardados siguientes de la misma instancia: solo cuenta el alta.
            $justCreated = $experience->wasRecentlyCreated && $experience->getChanges() === [];

            if ($experience->status === ExperienceStatus::Published && ($justCreated || $experience->wasChanged('status'))) {
                NotifyInterestedUsers::dispatch($experience, 'published')->afterCommit()->delay(now()->addMinute());
            }

            // Revisión automática con IA cada vez que entra a revisión o cambia el texto mientras espera.
            if ($experience->status === ExperienceStatus::InReview && ($justCreated || $experience->wasChanged(['status', ...self::MODERATED_FIELDS]))) {
                ModerateContent::experience($experience);
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
    /** Fotos de la galería que ya se pueden mostrar, en el orden que eligió el anfitrión. */
    public function galleryPhotos(): MorphMany
    {
        return $this->morphMany(Media::class, 'mediable')->where('collection', 'gallery')
            ->whereIn('status', Media::VISIBLE_STATUSES)->orderBy('position')->orderBy('id');
    }

    /** Todas las fotos de la galería, también las que se están procesando o se rechazaron. */
    public function galleryUploads(): MorphMany
    {
        return $this->morphMany(Media::class, 'mediable')->where('collection', 'gallery')->orderBy('position')->orderBy('id');
    }

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

    public function hasLocation(): bool
    {
        return $this->latitude !== null && $this->longitude !== null;
    }

    /**
     * Zona aproximada para mostrar en público: el punto real corrido unos
     * cientos de metros, siempre igual para la misma experiencia. El punto
     * exacto no sale nunca en una página pública.
     *
     * @return array{lat: float, lng: float, radius: int}|null
     */
    public function approximateLocation(): ?array
    {
        if (! $this->hasLocation()) {
            return null;
        }

        $radius = (int) config('tinku.maps.approximate_radius');
        $seed = crc32('tinku-zona-'.$this->id);
        $angle = deg2rad($seed % 360);
        $distance = $radius * 0.6 * (($seed >> 9) % 100) / 100;
        $lat = $this->latitude + ($distance * cos($angle)) / 111320;
        $lng = $this->longitude + ($distance * sin($angle)) / (111320 * max(cos(deg2rad($this->latitude)), 0.1));

        return ['lat' => round($lat, 5), 'lng' => round($lng, 5), 'radius' => $radius];
    }

    /** Enlace para llegar al punto exacto (solo para reservas confirmadas). */
    public function directionsUrl(): ?string
    {
        return $this->hasLocation()
            ? sprintf('https://www.openstreetmap.org/?mlat=%1$.6F&mlon=%2$.6F#map=17/%1$.6F/%2$.6F', $this->latitude, $this->longitude)
            : null;
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

    public function isPublished(): bool
    {
        return $this->status === ExperienceStatus::Published;
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
