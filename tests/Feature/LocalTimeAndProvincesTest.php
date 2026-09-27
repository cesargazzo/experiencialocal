<?php

namespace Tests\Feature;

use App\Enums\VerificationLevel;
use App\Livewire\HostOnboarding;
use App\Models\Category;
use App\Models\Experience;
use App\Models\ExperienceDate;
use App\Models\HostProfile;
use App\Models\Plan;
use App\Models\Province;
use App\Models\User;
use Database\Seeders\CategorySeeder;
use Database\Seeders\PlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class LocalTimeAndProvincesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([PlanSeeder::class, CategorySeeder::class]);
        Storage::fake('local');
        Storage::fake('public');
    }

    #[Test]
    public function all_argentine_jurisdictions_are_loaded_with_their_official_time_zone(): void
    {
        $this->assertSame(24, Province::where('country_code', 'AR')->count());
        $this->assertSame('America/Argentina/La_Rioja', Province::where('code', 'AR-F')->value('timezone'));
        $this->assertSame('America/Argentina/Salta', Province::where('name', 'Neuquén')->value('timezone'));
    }

    /**
     * @return Testable
     */
    private function fillOnboarding(User $host, string $provinceCode, string $plan = 'free')
    {
        return Livewire::actingAs($host)->test(HostOnboarding::class)
            ->set('display_name', 'Marta Quiroga')
            ->set('bio', str_repeat('Cocino recetas de mi familia en el patio de casa. ', 2))
            ->set('city', 'Chilecito')
            ->set('province_id', Province::where('code', $provinceCode)->value('id'))
            ->set('address', 'San Martín 123')
            ->set('plan', $plan)
            ->set('title', 'Empanadas en el patio de Marta')
            ->set('category_id', Category::value('id'))
            ->set('type_label', 'Cocina regional')
            ->set('summary', 'Amasamos y comemos empanadas riojanas.')
            ->set('description', str_repeat('Una tarde de cocina y sobremesa con recetas de familia en Chilecito. ', 2))
            ->set('price', 30000)
            ->set('max_guests', 6)
            ->set('duration_hours', 3)
            ->set('first_date', '2026-10-10')
            ->set('first_time', '20:30')
            ->set('cover', UploadedFile::fake()->image('patio.jpg', 1600, 1000));
    }

    #[Test]
    public function a_host_enters_local_time_and_it_is_stored_in_utc(): void
    {
        $host = User::factory()->level(VerificationLevel::Residence)->create();

        $this->fillOnboarding($host, 'AR-F')->call('publish')->assertHasNoErrors()->assertSet('step', 4);

        $experience = Experience::where('title', 'Empanadas en el patio de Marta')->firstOrFail();
        $date = $experience->dates()->firstOrFail();

        $this->assertSame('La Rioja', $experience->province->name);
        $this->assertSame('2026-10-10 23:30:00', $date->starts_at->utc()->format('Y-m-d H:i:s'));
        $this->assertSame('20:30', $date->localStart()->format('H:i'));
        $this->assertNotNull($experience->cover, 'La foto de la experiencia se guarda al publicar.');
    }

    #[Test]
    public function the_booking_widget_shows_times_in_the_local_time_of_the_place(): void
    {
        $this->travelTo(Carbon::parse('2026-10-01 12:00:00', 'UTC'));
        $date = ExperienceDate::factory()->create(['starts_at' => Carbon::parse('2026-10-10 23:30:00', 'UTC')]);

        $this->get(route('experiencias.show', $date->experience))
            ->assertOk()
            ->assertSee('20:30')
            ->assertSee('Horarios de La Rioja')
            ->assertSee('La Rioja');
    }

    #[Test]
    public function visitors_can_search_by_province_name(): void
    {
        $mendoza = Province::where('code', 'AR-M')->firstOrFail();
        Experience::factory()->create(['title' => 'Cata en Maipú', 'city' => 'Maipú', 'province_id' => $mendoza->id]);
        Experience::factory()->create(['title' => 'Empanadas en Chilecito']);

        $this->get(route('home', ['lugar' => 'mendoza']))
            ->assertOk()
            ->assertSee('Cata en Maipú')
            ->assertDontSee('Empanadas en Chilecito');
    }

    #[Test]
    public function the_plan_limit_is_shown_as_a_message_instead_of_failing(): void
    {
        $host = User::factory()->level(VerificationLevel::Residence)->create();
        $profile = HostProfile::factory()->for($host)->create(['plan_id' => Plan::where('slug', 'free')->value('id')]);
        Experience::factory()->for($profile, 'host')->create();

        $this->fillOnboarding($host, 'AR-F', 'free')->call('publish')
            ->assertHasErrors('title')
            ->assertSet('step', 1);
    }

    #[Test]
    public function vertical_phone_photos_are_accepted_and_tiny_ones_get_a_clear_message(): void
    {
        $host = User::factory()->level(VerificationLevel::Residence)->create();

        $this->fillOnboarding($host, 'AR-F')
            ->set('cover', UploadedFile::fake()->image('vertical.jpg', 720, 1280))
            ->call('publish')
            ->assertHasNoErrors();

        $other = User::factory()->level(VerificationLevel::Residence)->create();
        $this->fillOnboarding($other, 'AR-F')
            ->set('cover', UploadedFile::fake()->image('mini.jpg', 500, 400))
            ->call('publish')
            ->assertHasErrors(['cover' => 'La foto mide 500 × 400 px y tiene que tener al menos 600 px de cada lado. Probá con la original del celular, sin recortar.']);
    }
}
