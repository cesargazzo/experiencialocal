<?php

namespace Database\Factories;

use App\Enums\ExperienceStatus;
use App\Models\Category;
use App\Models\Experience;
use App\Models\HostProfile;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Experience> */
class ExperienceFactory extends Factory
{
    public function definition(): array
    {
        $title = fake()->unique()->sentence(3);

        return [
            'host_profile_id' => HostProfile::factory(),
            'category_id' => fn () => Category::firstOrCreate(['slug' => 'comida'], ['name' => 'Comidas', 'icon' => 'fork-knife'])->id,
            'title' => $title,
            'slug' => Str::slug($title),
            'type_label' => 'Cocina regional',
            'summary' => fake()->sentence(8),
            'description' => fake()->paragraphs(2, true),
            'city' => 'La Rioja',
            'country_code' => 'AR',
            'price' => 40000,
            'currency' => 'ARS',
            'duration_minutes' => 180,
            'max_guests' => 6,
            'includes' => [],
            'status' => ExperienceStatus::Published,
            'published_at' => now(),
        ];
    }

    public function inReview(): static
    {
        return $this->state(fn () => ['status' => ExperienceStatus::InReview, 'published_at' => null]);
    }
}
