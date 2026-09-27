<?php

namespace Database\Factories;

use App\Enums\VerificationLevel;
use App\Models\User;
use App\Support\Totp;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<User> */
class UserFactory extends Factory
{
    protected static ?string $password;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'phone' => fake()->phoneNumber(),
            'phone_verified_at' => now(),
            'birth_date' => fake()->dateTimeBetween('-70 years', '-19 years')->format('Y-m-d'),
            'password' => static::$password ??= 'password',
            'country_code' => 'AR',
            'nationality_code' => 'AR',
            'verification_level' => VerificationLevel::Document,
            'remember_token' => Str::random(10),
        ];
    }

    public function level(VerificationLevel $level): static
    {
        return $this->state(fn () => ['verification_level' => $level]);
    }

    public function admin(): static
    {
        return $this->state(fn () => [
            'is_admin' => true,
            'verification_level' => VerificationLevel::Residence,
            // Los admins tienen doble factor: la administración lo exige.
            'two_factor_secret' => Totp::generateSecret(),
            'two_factor_confirmed_at' => now(),
        ]);
    }

    public function foreign(string $nationality = 'IT'): static
    {
        return $this->state(fn () => ['nationality_code' => $nationality, 'country_code' => $nationality]);
    }
}
