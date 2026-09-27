<?php

namespace App\Models;

use App\Enums\VerificationLevel;
use App\Support\PasswordPolicy;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

/**
 * Una sola cuenta con varios roles: todo usuario es participante, es anfitrión
 * si tiene un HostProfile y es administrador si tiene el flag is_admin.
 */
#[Fillable(['name', 'email', 'password', 'phone', 'country_code', 'nationality_code', 'locale', 'avatar_path'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'phone_verified_at' => 'datetime',
            'suspended_at' => 'datetime',
            'password' => 'hashed',
            'must_change_password' => 'boolean',
            'password_expires_at' => 'datetime',
            'password_changed_at' => 'datetime',
            'verification_level' => VerificationLevel::class,
            'is_admin' => 'boolean',
        ];
    }

    public function hostProfile(): HasOne
    {
        return $this->hasOne(HostProfile::class);
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
