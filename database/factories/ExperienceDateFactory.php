<?php

namespace Database\Factories;

use App\Models\Experience;
use App\Models\ExperienceDate;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ExperienceDate> */
class ExperienceDateFactory extends Factory
{
    public function definition(): array
    {
        return [
            'experience_id' => Experience::factory(),
            'starts_at' => now()->addWeek(),
            'ends_at' => now()->addWeek()->addHours(3),
            'capacity' => 6,
            'booked_count' => 0,
            'status' => 'open',
        ];
    }
}
