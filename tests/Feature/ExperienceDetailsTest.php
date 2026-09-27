<?php

namespace Tests\Feature;

use App\Enums\DietaryOption;
use App\Enums\Difficulty;
use App\Enums\ExperienceFeature;
use App\Enums\ExperienceStatus;
use App\Enums\VerificationLevel;
use App\Livewire\HostOnboarding;
use App\Livewire\ManageExperience;
use App\Models\Category;
use App\Models\Experience;
use App\Models\Province;
use App\Models\User;
use Database\Seeders\CategorySeeder;
use Database\Seeders\PlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ExperienceDetailsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([PlanSeeder::class, CategorySeeder::class]);
        Storage::fake('local');
        Storage::fake('public');
        Queue::fake();
    }

    private function onboarding(string $categorySlug): Testable
    {
        return Livewire::actingAs(User::factory()->level(VerificationLevel::Residence)->create())->test(HostOnboarding::class)
            ->set('bio', str_repeat('Recorro los cerros de mi pueblo desde chico con mi familia. ', 2))
            ->set('city', 'Chilecito')
            ->set('province_id', Province::where('code', 'AR-F')->value('id'))
            ->set('address', 'San Martín 123')
            ->set('title', 'Subida al cerro con Ramiro')
            ->set('category_id', Category::where('slug', $categorySlug)->value('id'))
            ->set('type_label', 'Caminata')
            ->set('summary', 'Subimos al mirador y bajamos al atardecer.')
            ->set('description', str_repeat('Una caminata por senderos de la sierra con vistas a todo el valle. ', 2))
            ->set('price', 25000)
            ->set('max_guests', 8)
            ->set('first_date', now()->addWeek()->toDateString())
            ->set('cover', UploadedFile::fake()->image('cerro.jpg', 1600, 1000))
            ->set('step', 2);
    }

    #[Test]
    public function a_walk_asks_for_difficulty_and_keeps_food_options_only_if_there_is_food(): void
    {
        $this->onboarding('paseo')->call('publish')->assertHasErrors(['difficulty' => 'Indicá la dificultad. Ayuda a elegir a quien reserva.']);

        $this->onboarding('paseo')
            ->assertDontSee('Apto vegano')
            ->set('difficulty', 'moderate')
            ->set('what_to_bring', 'Zapatillas de trekking, agua y abrigo.')
            ->set('min_age', 10)
            ->set('features', ['kids', 'transport'])
            ->set('dietary_options', ['vegan'])
            ->call('publish')
            ->assertHasNoErrors();

        $experience = Experience::where('title', 'Subida al cerro con Ramiro')->firstOrFail();
        $this->assertSame(Difficulty::Moderate, $experience->difficulty);
        $this->assertSame('Zapatillas de trekking, agua y abrigo.', $experience->what_to_bring);
        $this->assertSame(10, $experience->min_age);
        $this->assertEquals([ExperienceFeature::KidFriendly, ExperienceFeature::TransportIncluded], $experience->features->all());
        $this->assertTrue($experience->dietary_options->isEmpty(), 'Sin comida no quedan opciones de comida.');
    }

    #[Test]
    public function a_walk_with_a_snack_can_list_food_options_and_a_meal_never_asks_for_difficulty(): void
    {
        $this->onboarding('paseo')
            ->set('difficulty', 'easy')
            ->set('includes_food', true)
            ->assertSee('Apto vegano')
            ->set('dietary_options', ['vegan'])
            ->call('publish')
            ->assertHasNoErrors();
        $this->assertEquals([DietaryOption::Vegan], Experience::where('title', 'Subida al cerro con Ramiro')->value('dietary_options')->all());

        $this->onboarding('comida')->assertSee('Apto vegano')->assertDontSee('Exigente');
    }

    #[Test]
    public function the_experience_page_shows_what_is_good_to_know(): void
    {
        $experience = Experience::factory()->create([
            'category_id' => Category::where('slug', 'paseo')->value('id'),
            'difficulty' => 'hard', 'what_to_bring' => 'Bastones y abrigo.', 'min_age' => 14, 'features' => ['pets'],
        ]);

        $this->get(route('experiencias.show', $experience))
            ->assertOk()
            ->assertSee('Bueno saber')
            ->assertSee('Dificultad alta')
            ->assertSee('Qué llevar y cómo vestirse')
            ->assertSee('Bastones y abrigo.')
            ->assertSee('Desde 14 años')
            ->assertSee('Se aceptan mascotas');
    }

    #[Test]
    public function editing_the_difficulty_applies_right_away_but_what_to_bring_is_reviewed(): void
    {
        $experience = Experience::factory()->create(['category_id' => Category::where('slug', 'paseo')->value('id'), 'difficulty' => 'easy']);

        Livewire::actingAs($experience->host->user)->test(ManageExperience::class, ['experience' => $experience])
            ->set('difficulty', 'hard')
            ->call('save')
            ->assertHasNoErrors();
        $this->assertSame(Difficulty::Hard, $experience->fresh()->difficulty);
        $this->assertSame(ExperienceStatus::Published, $experience->fresh()->status);

        Livewire::actingAs($experience->host->user)->test(ManageExperience::class, ['experience' => $experience->fresh()])
            ->set('what_to_bring', 'Traé ropa cómoda.')
            ->call('save')
            ->assertHasNoErrors();
        $this->assertSame(ExperienceStatus::InReview, $experience->fresh()->status);
    }
}
