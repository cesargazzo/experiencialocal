<?php

namespace App\Livewire\Concerns;

use App\Enums\DietaryOption;
use App\Enums\Difficulty;
use App\Enums\ExperienceFeature;
use App\Models\Category;
use App\Models\Experience;
use Illuminate\Validation\Rule;

/**
 * Datos prácticos de una experiencia que dependen de su categoría: opciones de
 * comida, dificultad, qué llevar, edad mínima y extras. Lo usan el alta y la edición.
 */
trait EditsExperienceDetails
{
    /** @var list<string> */
    public array $dietary_options = [];

    /** En categorías sin comida, el anfitrión indica si igual hay algo para comer. */
    public bool $includes_food = false;

    public ?string $difficulty = null;

    public string $what_to_bring = '';

    public ?int $min_age = null;

    /** @var list<string> */
    public array $features = [];

    public function selectedCategory(): ?Category
    {
        return $this->category_id ? Category::find($this->category_id) : null;
    }

    public function showsFoodOptions(): bool
    {
        return (bool) $this->selectedCategory()?->has_food || $this->includes_food;
    }

    /**
     * @return array<string, mixed>
     */
    protected function detailRules(): array
    {
        return [
            'dietary_options' => ['array'],
            'dietary_options.*' => [Rule::enum(DietaryOption::class)],
            'difficulty' => [$this->selectedCategory()?->has_difficulty ? 'required' : 'nullable', Rule::enum(Difficulty::class)],
            'what_to_bring' => ['nullable', 'string', 'max:500'],
            'min_age' => ['nullable', 'integer', 'min:0', 'max:99'],
            'features' => ['array'],
            'features.*' => [Rule::enum(ExperienceFeature::class)],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function detailMessages(): array
    {
        return ['difficulty.required' => 'Indicá la dificultad. Ayuda a elegir a quien reserva.'];
    }

    /**
     * Lo que se guarda en la experiencia. Sin comida, no quedan opciones de comida.
     *
     * @return array<string, mixed>
     */
    protected function detailAttributes(): array
    {
        return [
            'dietary_options' => $this->showsFoodOptions() ? array_values(array_unique($this->dietary_options)) : [],
            'difficulty' => $this->selectedCategory()?->has_difficulty ? $this->difficulty : null,
            'what_to_bring' => trim($this->what_to_bring) ?: null,
            'min_age' => $this->min_age ?: null,
            'features' => array_values(array_unique($this->features)),
        ];
    }

    protected function fillDetailsFrom(Experience $experience): void
    {
        $this->dietary_options = $experience->dietary_options?->map->value->all() ?? [];
        $this->includes_food = $this->dietary_options !== [];
        $this->difficulty = $experience->difficulty?->value;
        $this->what_to_bring = $experience->what_to_bring ?? '';
        $this->min_age = $experience->min_age;
        $this->features = $experience->features?->map->value->all() ?? [];
    }
}
