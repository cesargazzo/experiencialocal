<?php

namespace App\Models;

use App\Enums\BookingStatus;
use App\Enums\DietaryOption;
use App\Enums\ExperienceFeature;
use App\Enums\VerificationLevel;
use App\Models\Concerns\Auditable;
use App\Notifications\ResetPasswordNotification;
use App\Support\CountryList;
use App\Support\PasswordPolicy;
use App\Support\Totp;
use Carbon\CarbonInterface;
use Carbon\CarbonPeriod;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\AsEnumCollection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Una sola cuenta con varios roles: todo usuario es participante, es anfitrión
 * si tiene un HostProfile y es administrador si tiene el flag is_admin.
 */
#[Fillable(['name', 'first_name', 'last_name', 'email', 'password', 'phone', 'birth_date', 'country_code', 'province_id', 'city', 'postal_code', 'dietary_needs', 'food_allergies', 'required_features', 'nationality_code', 'locale', 'avatar_path'])]
#[Hidden(['password', 'remember_token', 'two_factor_secret', 'two_factor_recovery_codes'])]
class User extends Authenticatable
{
    use Auditable;

    /** @var list<string> Salud y religión: datos sensibles, no se guardan en la auditoría. */
    protected array $auditMasked = ['dietary_needs', 'food_allergies'];

    /** @var list<string> Secretos del doble factor: nunca van a la auditoría. */
    protected array $auditExclude = ['two_factor_secret', 'two_factor_recovery_codes', 'two_factor_last_step'];

    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected static function booted(): void
    {
        // "name" es siempre el nombre completo. Si llega solo "name" (datos viejos, fábricas),
        // se separa: la primera palabra es el nombre y el resto el apellido.
        static::saving(function (User $user): void {
            if ($user->isDirty(['first_name', 'last_name']) && filled($user->first_name)) {
                $user->first_name = trim((string) $user->first_name);
                $user->last_name = trim((string) $user->last_name) ?: null;
                $user->name = trim($user->first_name.' '.$user->last_name);
            } elseif ($user->isDirty('name') || blank($user->first_name)) {
                $parts = preg_split('/\s+/u', trim((string) $user->name), 2) ?: [''];
                $user->first_name = $parts[0];
                $user->last_name = $parts[1] ?? null;
            }
        });
    }

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
            'required_features' => AsEnumCollection::of(ExperienceFeature::class),
            'last_login_at' => 'datetime',
            'two_factor_secret' => 'encrypted',
            'two_factor_recovery_codes' => 'encrypted:array',
            'two_factor_confirmed_at' => 'datetime',
            'last_seen_at' => 'datetime',
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

    /**
     * Cuentas que cumplen años entre dos fechas (inclusive), sin importar el año.
     * Quien nació un 29 de febrero lo festeja el 28 en los años no bisiestos.
     */
    public function scopeBirthdayBetween(Builder $query, CarbonInterface $from, CarbonInterface $to): Builder
    {
        $days = collect(CarbonPeriod::create($from->copy()->startOfDay(), $to->copy()->startOfDay()))
            ->flatMap(fn (CarbonInterface $day) => $day->month === 2 && $day->day === 28 && ! $day->isLeapYear()
                ? [[2, 28], [2, 29]]
                : [[$day->month, $day->day]])
            ->unique(fn (array $pair) => implode('-', $pair));

        return $query->whereNotNull('birth_date')->where(function (Builder $query) use ($days): void {
            foreach ($days as [$month, $day]) {
                $query->orWhere(fn (Builder $q) => $q->whereMonth('birth_date', $month)->whereDay('birth_date', $day));
            }
        });
    }

    /** El primer cumpleaños desde la fecha dada (inclusive), en la zona horaria de esa fecha. */
    public function birthdayOnOrAfter(CarbonInterface $from): ?CarbonInterface
    {
        if (! $this->birth_date) {
            return null;
        }

        $from = $from->copy()->startOfDay();
        foreach ([$from->year, $from->year + 1] as $year) {
            $day = $this->birth_date->month === 2 && $this->birth_date->day === 29 && ! Carbon::create($year)->isLeapYear() ? 28 : $this->birth_date->day;
            $birthday = $from->copy()->setDate($year, $this->birth_date->month, $day);
            if ($birthday->gte($from)) {
                return $birthday;
            }
        }

        return null;
    }

    public function isBirthdayToday(): bool
    {
        $today = now(config('tinku.timezone'));

        return (bool) $this->birthdayOnOrAfter($today)?->isSameDay($today);
    }

    /** Años que cumple en la fecha dada. */
    public function ageTurningOn(CarbonInterface $reference): ?int
    {
        return $this->birth_date ? $reference->year - $this->birth_date->year : null;
    }

    /**
     * El apellido solo lo ven quienes tienen la identidad validada (nivel 2),
     * la propia persona y el equipo. El resto ve solo el nombre de pila.
     */
    public static function viewerSeesLastNames(?User $viewer = null): bool
    {
        $viewer ??= auth()->user();

        return (bool) $viewer?->hasVerificationLevel(VerificationLevel::Document) || (bool) $viewer?->isAdmin();
    }

    /** Nombre para mostrar a quien está mirando. */
    public function publicName(?User $viewer = null): string
    {
        $viewer ??= auth()->user();

        return $viewer?->is($this) || self::viewerSeesLastNames($viewer) ? $this->name : ($this->first_name ?: Str::before($this->name.' ', ' '));
    }

    public function hasTwoFactor(): bool
    {
        return $this->two_factor_confirmed_at !== null && filled($this->two_factor_secret);
    }

    /**
     * Valida un código de la app o uno de recuperación (que se consume).
     *
     * @return 'totp'|'recovery'|null
     */
    public function verifyTwoFactorCode(string $code): ?string
    {
        if (! $this->two_factor_secret) {
            return null;
        }

        $step = Totp::verify($this->two_factor_secret, $code, $this->two_factor_last_step);
        if ($step !== null) {
            $this->forceFill(['two_factor_last_step' => $step])->save();

            return 'totp';
        }

        $normalized = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $code) ?? '');
        foreach ($this->two_factor_recovery_codes ?? [] as $index => $hash) {
            if ($normalized !== '' && Hash::check($normalized, $hash)) {
                $codes = $this->two_factor_recovery_codes;
                unset($codes[$index]);
                $this->forceFill(['two_factor_recovery_codes' => array_values($codes)])->save();

                return 'recovery';
            }
        }

        return null;
    }

    /**
     * Genera códigos de recuperación nuevos. Se guardan con hash; los visibles se muestran una sola vez.
     *
     * @return list<string>
     */
    public function regenerateRecoveryCodes(): array
    {
        $codes = collect(range(1, 8))->map(fn () => strtoupper(Str::random(5).'-'.Str::random(5)))->all();
        $this->forceFill(['two_factor_recovery_codes' => array_map(fn (string $code) => Hash::make(str_replace('-', '', $code)), $codes)])->save();

        return $codes;
    }

    public function termsAcceptances(): HasMany
    {
        return $this->hasMany(TermsAcceptance::class);
    }

    /** Si aceptó la versión exigida o una posterior. Sin términos publicados no hay nada que aceptar. */
    public function hasAcceptedRequiredTerms(): bool
    {
        $required = TermsVersion::required();

        return $required === null || $this->termsAcceptances()
            ->whereHas('version', fn ($query) => $query->where('published_at', '>=', $required->published_at))
            ->exists();
    }

    /** Reservas de sus experiencias que esperan que las confirme. */
    public function pendingHostBookingsCount(): int
    {
        return $this->hostProfile
            ? Booking::query()
                ->where('status', BookingStatus::Requested)
                ->whereHas('experience', fn ($q) => $q->where('host_profile_id', $this->hostProfile->id))
                ->whereHas('date', fn ($q) => $q->where('starts_at', '>', now()))
                ->count()
            : 0;
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
