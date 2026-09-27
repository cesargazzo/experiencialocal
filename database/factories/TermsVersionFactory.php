<?php

namespace Database\Factories;

use App\Models\TermsVersion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TermsVersion>
 */
class TermsVersionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'version' => fake()->unique()->numerify('#.#'),
            'title' => 'Términos y condiciones de Tinku',
            'body' => fake()->paragraphs(4, true),
            'changes_summary' => null,
            'requires_reacceptance' => true,
        ];
    }

    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'published_at' => now(),
            'body_hash' => hash('sha256', $attributes['body']),
        ]);
    }
}
