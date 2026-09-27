<?php

namespace App\Models;

use App\Enums\DietaryOption;
use App\Enums\VerificationLevel;
use App\Models\Concerns\Auditable;
use App\Notifications\ResetPasswordNotification;
use App\Support\CountryList;
use App\Support\PasswordPolicy;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Casts\AsEnumCollection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

/**
 * Una sola cuenta con varios roles: todo usuario es participante, es anfitrión
 * si tiene un HostProfile y es administrador si tiene el flag is_admin.
 */
#[Fillable(['name', 'email', 'password', 'phone', 'birth_date', 'country_code', 'province_id', 'city', 'postal_code', 'dietary_needs', 'food_allergies', 'nationality_code', 'locale', 'avatar_path'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    use Auditable;

    /** @var list<string> Salud y religión: datos sensibles, no se guardan en la auditoría. */
    protected array $auditMasked = ['dietary_needs', 'food_allergies'];

    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'phone_verified_at' => 'datetime',
            'birth_date' => 'date',
            'suspended_at' => 'datetime',
            'password' => 'hashed',
            'must_change_password' => 'boolean',
            'password_expires_at' => 'datetime',
            'password_changed_at' => 'datetime',
            'verification_level' => VerificationLevel::class,
            'is_admin' => 'boolean',
            'interest_alerts' => 'boolean',
            'dietary_needs' => AsEnumCollection::of(DietaryOption::class),
        ];
    }

    public function sendPasswordResetNotification(#[\SensitiveParameter] $token): void
    {
        $this->notify(new ResetPasswordNotification($token));
    }

    public function interestedCategories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class);
    }

    public function interestedProvinces(): BelongsToMany
    {
        return $this->belongsToMany(Province::class);
    }

    public function hasInterests(): bool
    {
        return $this->interestedCategories()->exists() || $this->interestedProvinces()->exists();
    }

    public function securityEvents(): HasMany
    {
        return $this->hasMany(SecurityEvent::class);
    }

    public function invitations(): HasMany
    {
        return $this->hasMany(Invitation::class, 'inviter_id');
    }

    public function invitedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by');
    }

    public function province(): BelongsTo
    {
        return $this->belongsTo(Province::class);
    }

    /** Dónde vive, para mostrar: "Chilecito, La Rioja, Argentina". */
    public function locationLabel(): ?string
    {
        $parts = array_filter([$this->city, $this->province?->name, CountryList::all()[$this->country_code] ?? null]);

        return $parts === [] ? null : implode(', ', $parts);
    }

    public function hasDietaryNeeds(): bool
    {
        return (bool) $this->dietary_needs?->isNotEmpty() || filled($this->food_allergies);
    }

    public function hostProfile(): HasOne
    {
        return $this->hasOne(HostProfile::class);
    }

    public function avatar(): MorphOne
    {
        // Los filtros van dentro de ofMany: elige la última foto *lista*, no la última subida.
        return $this->morphOne(Media::class, 'mediable')->ofMany(
            ['id' => 'max'],
            fn ($query) => $query->where('collection', 'avatar')->whereIn('status', Media::VISIBLE_STATUSES),
        );
    }

    /** La última foto subida, esté lista o no: sirve para mostrar "procesando" o un error. */
    public function latestAvatarUpload(): MorphOne
    {
        return $this->morphOne(Media::class, 'mediable')->ofMany(['id' => 'max'], fn ($query) => $query->where('collection', 'avatar'));
    }

    public function verifications(): HasMany
    {
        return $this->hasMany(IdentityVerification::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function isAdmin(): bool
    {
        return (bool) $this->is_admin;
    }

    public function isHost(): bool
    {
        return $this->hostProfile()->exists();
    }

    public function isSuspended(): bool
    {
        return $this->suspended_at !== null;
    }

    /**
     * Asigna una contraseña de única vez: vence a las 24 horas y obliga a
     * elegir una nueva en el primer ingreso. Devuelve la contraseña en claro
     * para entregarla una sola vez.
     */
    public function issueTemporaryPassword(int $validForHours = 24): string
    {
        $length = max(16, PasswordPolicy::current()->minLength);
        $temporary = Str::password($length);

        $this->forceFill([
            'password' => $temporary,
            'must_change_password' => true,
            'password_expires_at' => now()->addHours($validForHours),
        ])->save();

        return $temporary;
    }

    public function changePassword(string $newPassword): void
    {
        $this->forceFill([
            'password' => $newPassword,
            'must_change_password' => false,
            'password_expires_at' => null,
            'password_changed_at' => now(),
        ])->save();
    }

    public function hasExpiredPassword(): bool
    {
        return $this->password_expires_at !== null && $this->password_expires_at->isPast();
    }

    /**
     * Reglas para la fecha de nacimiento: fecha real y edad mínima.
     *
     * @return list<string>
     */
    public static function birthDateRules(): array
    {
        return [
            'date',
            'after:1900-01-01',
            'before_or_equal:'.now()->subYears((int) config('tinku.min_age'))->toDateString(),
        ];
    }

    public function hasVerificationLevel(VerificationLevel $level): bool
    {
        return $this->verification_level->atLeast($level);
    }

    public function initials(): string
    {
        return collect(explode(' ', trim($this->name)))
            ->filter()
            ->take(2)
            ->map(fn (string $part) => mb_strtoupper(mb_substr($part, 0, 1)))
            ->implode('');
    }
}
