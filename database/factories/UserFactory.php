<?php

namespace Database\Factories;

use App\Enums\VerificationLevel;
use App\Models\User;
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
        return $this->state(fn () => ['is_admin' => true, 'verification_level' => VerificationLevel::Residence]);
    }

    public function foreign(string $nationality = 'IT'): static
    {
        return $this->state(fn () => ['nationality_code' => $nationality, 'country_code' => $nationality]);
    }
}
