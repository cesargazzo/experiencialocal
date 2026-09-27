<?php

namespace Database\Factories;

use App\Models\Plan;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Plan> */
class PlanFactory extends Factory
{
    public function definition(): array
    {
        return [
            'slug' => fake()->unique()->slug(1),
            'name' => 'Free',
            'tagline' => 'Para probar',
            'monthly_price' => 0,
            'commission_rate' => 0.18,
            'max_experiences' => 1,
            'features' => [],
        ];
    }
}
