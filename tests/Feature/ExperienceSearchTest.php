<?php

namespace Tests\Feature;

use App\Enums\DietaryOption;
use App\Enums\Difficulty;
use App\Enums\ExperienceFeature;
use App\Models\Experience;
use App\Models\ExperienceDate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ExperienceSearchTest extends TestCase
{
    use RefreshDatabase;

    private function titles(array $query): array
    {
        return $this->get(route('home', $query))->assertOk()->viewData('experiences')->pluck('title')->all();
    }

    #[Test]
    public function it_filters_by_food_needs_features_difficulty_and_price(): void
    {
        Experience::factory()->create(['title' => 'Cena vegana', 'price' => 30000, 'dietary_options' => [DietaryOption::Vegan], 'features' => [ExperienceFeature::PetFriendly]]);
        Experience::factory()->create(['title' => 'Asado celíaco', 'price' => 60000, 'dietary_options' => [DietaryOption::SinTacc]]);
        Experience::factory()->create(['title' => 'Trekking', 'price' => 45000, 'difficulty' => Difficulty::Hard, 'features' => [ExperienceFeature::KidFriendly]]);

        // Vegano cubre a quien busca vegetariano; sin TACC cubre "sin gluten".
        $this->assertSame(['Cena vegana'], $this->titles(['comida' => ['vegetarian']]));
        $this->assertSame(['Asado celíaco'], $this->titles(['comida' => ['gluten_free']]));
        $this->assertSame([], $this->titles(['comida' => ['vegan', 'sin_tacc']]));
        $this->assertSame(['Cena vegana'], $this->titles(['necesito' => ['pets']]));
        $this->assertSame(['Trekking'], $this->titles(['dificultad' => ['hard']]));
        $this->assertEqualsCanonicalizing(['Cena vegana', 'Trekking'], $this->titles(['precio_max' => 50000]));
        $this->assertSame(['Cena vegana', 'Trekking', 'Asado celíaco'], $this->titles(['orden' => 'precio']));
    }

    #[Test]
    public function date_and_people_need_an_open_date_with_enough_seats(): void
    {
        $this->travelTo(Carbon::parse('2026-10-01 12:00:00'));
        $full = Experience::factory()->create(['title' => 'Casi llena']);
        ExperienceDate::factory()->for($full)->create(['starts_at' => '2026-10-10 23:30:00', 'capacity' => 6, 'booked_count' => 5]);
        $free = Experience::factory()->create(['title' => 'Con lugar']);
        // 20:30 en Argentina del 10 de octubre son las 23:30 UTC.
        ExperienceDate::factory()->for($free)->create(['starts_at' => '2026-10-10 23:30:00']);
        Experience::factory()->create(['title' => 'Sin fechas']);

        $this->assertSame(['Con lugar'], $this->titles(['fecha' => '2026-10-10', 'personas' => 2]));
        $this->assertEqualsCanonicalizing(['Casi llena', 'Con lugar'], $this->titles(['fecha' => '2026-10-10']));
        $this->assertSame([], $this->titles(['fecha' => '2026-10-11']));
        $this->assertSame(['Con lugar'], $this->titles(['personas' => 3]));
    }

    #[Test]
    public function bad_values_in_the_url_are_ignored_instead_of_failing(): void
    {
        Experience::factory()->create(['title' => 'Una']);

        $this->assertSame(['Una'], $this->titles(['comida' => ['pizza'], 'personas' => 'muchas', 'orden' => 'raro', 'fecha' => '2020-01-01']));
        $this->get(route('home', ['comida' => ['vegan']]))->assertSee('Más filtros')->assertSee('con esos filtros');
    }
}
