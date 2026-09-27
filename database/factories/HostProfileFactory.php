<?php

namespace Database\Factories;

use App\Enums\HostStatus;
use App\Models\HostProfile;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<HostProfile> */
class HostProfileFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'plan_id' => Plan::factory(),
            'display_name' => fake()->name(),
            'bio' => fake()->paragraph(),
            'city' => 'La Rioja',
            'province' => 'La Rioja',
            'country_code' => 'AR',
            'address' => fake()->streetAddress(),
            'status' => HostStatus::Active,
            'hosting_since' => now()->subYear(),
        ];
    }

    public function inReview(): static
    {
        return $this->state(fn () => ['status' => HostStatus::InReview]);
    }
}
