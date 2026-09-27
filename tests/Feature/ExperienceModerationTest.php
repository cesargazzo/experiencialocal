<?php

namespace Tests\Feature;

use App\Enums\DietaryOption;
use App\Enums\ExperienceStatus;
use App\Enums\VerificationLevel;
use App\Exceptions\BookingException;
use App\Livewire\HostOnboarding;
use App\Models\Category;
use App\Models\Experience;
use App\Models\ExperienceDate;
use App\Models\HostProfile;
use App\Models\Media;
use App\Models\Province;
use App\Models\User;
use App\Notifications\ExperienceReviewedNotification;
use App\Services\BookingService;
use Database\Seeders\CategorySeeder;
use Database\Seeders\PlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ExperienceModerationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([PlanSeeder::class, CategorySeeder::class]);
        Storage::fake('local');
        Storage::fake('public');
        Notification::fake();
    }

    private function fillOnboarding(User $host): Testable
    {
        return Livewire::actingAs($host)->test(HostOnboarding::class)
            ->set('bio', str_repeat('Cocino recetas de mi familia en el patio de casa. ', 2))
            ->set('city', 'Chilecito')
            ->set('province_id', Province::where('code', 'AR-F')->value('id'))
            ->set('address', 'San Martín 123')
            ->set('title', 'Empanadas en el patio de Marta')
            ->set('category_id', Category::value('id'))
            ->set('type_label', 'Cocina regional')
            ->set('summary', 'Amasamos y comemos empanadas riojanas.')
            ->set('description', str_repeat('Una tarde de cocina y sobremesa con recetas de familia en Chilecito. ', 2))
            ->set('price', 30000)
            ->set('max_guests', 6)
            ->set('first_date', now()->addWeek()->toDateString())
            ->set('cover', UploadedFile::fake()->image('patio.jpg', 1600, 1000));
    }

    #[Test]
    public function a_new_experience_waits_for_review_even_for_a_fully_verified_host(): void
    {
        $host = User::factory()->level(VerificationLevel::Residence)->create();

        $this->fillOnboarding($host)
            ->set('dietary_options', ['vegan', 'sin_tacc'])
            ->call('publish')
            ->assertHasNoErrors()
            ->assertSet('step', 4);

        $experience = Experience::where('title', 'Empanadas en el patio de Marta')->firstOrFail();
        $this->assertSame(ExperienceStatus::InReview, $experience->status);
        $this->assertNull($experience->published_at);
        $this->assertEquals([DietaryOption::Vegan, DietaryOption::SinTacc], $experience->dietary_options->all());

        $this->get(route('home'))->assertDontSee('Empanadas en el patio de Marta');
    }

    #[Test]
    public function only_known_dietary_options_are_accepted(): void
    {
        $this->fillOnboarding(User::factory()->level(VerificationLevel::Residence)->create())
            ->set('dietary_options', ['carnivoro'])
            ->call('publish')
            ->assertHasErrors('dietary_options.0');
    }

    #[Test]
    public function an_admin_approves_and_it_is_published_with_its_food_options(): void
    {
        $admin = User::factory()->admin()->create();
        $experience = Experience::factory()->inReview()->create(['dietary_options' => ['vegetarian', 'gluten_free']]);

        $this->actingAs($admin)->get(route('admin.experiencias'))->assertOk()->assertSee($experience->title)->assertSee('Experiencias (1)');
        $this->actingAs($admin)->post(route('admin.experiencias.aprobar', $experience))->assertSessionHasNoErrors();

        $experience->refresh();
        $this->assertSame(ExperienceStatus::Published, $experience->status);
        $this->assertSame($admin->id, $experience->approved_by);
        Notification::assertSentTo($experience->host->user, ExperienceReviewedNotification::class, fn ($notification) => $notification->outcome === ExperienceReviewedNotification::APPROVED);

        $this->get(route('experiencias.show', $experience))->assertOk()->assertSee('Apto vegetariano')->assertSee('la cocina no es libre de TACC');
    }

    #[Test]
    public function a_rejection_needs_a_reason_and_goes_back_to_the_host(): void
    {
        $admin = User::factory()->admin()->create();
        $experience = Experience::factory()->inReview()->create();

        $this->actingAs($admin)->post(route('admin.experiencias.rechazar', $experience))->assertSessionHasErrors('reason');
        $this->actingAs($admin)->post(route('admin.experiencias.rechazar', $experience), ['reason' => 'La foto es de stock.'])->assertSessionHasNoErrors();

        $experience->refresh();
        $this->assertSame(ExperienceStatus::Draft, $experience->status);
        $this->assertSame('La foto es de stock.', $experience->rejection_reason);
        Notification::assertSentTo($experience->host->user, ExperienceReviewedNotification::class, fn ($notification) => $notification->outcome === ExperienceReviewedNotification::REJECTED);

        $this->actingAs($experience->host->user)->get(route('experiencias.show', $experience))->assertSee('Motivo: La foto es de stock.')
            ->assertSee(route('anfitrion.experiencias.editar', $experience), false);
    }

    #[Test]
    public function an_approved_experience_of_a_host_in_review_is_published_when_the_host_reaches_level_three(): void
    {
        $admin = User::factory()->admin()->create();
        $host = User::factory()->level(VerificationLevel::Document)->create();
        $profile = HostProfile::factory()->inReview()->for($host)->create();
        $approved = Experience::factory()->inReview()->for($profile, 'host')->create();
        $notReviewed = Experience::factory()->inReview()->for($profile, 'host')->create();

        $this->actingAs($admin)->post(route('admin.experiencias.aprobar', $approved));
        $this->assertSame(ExperienceStatus::InReview, $approved->fresh()->status);
        $this->assertNotNull($approved->fresh()->approved_at);

        $this->actingAs($admin)->post(route('admin.usuarios.validar'), ['users' => [$host->id], 'level' => '3', 'reason' => 'Visité el domicilio', 'document_country' => 'AR', 'document_number' => '28999111']);

        $this->assertSame(ExperienceStatus::Published, $approved->fresh()->status);
        $this->assertSame(ExperienceStatus::InReview, $notReviewed->fresh()->status);
    }

    #[Test]
    public function the_review_list_says_when_the_photo_is_still_processing_instead_of_a_broken_image(): void
    {
        $experience = Experience::factory()->inReview()->create();
        $experience->morphMany(Media::class, 'mediable')->create([
            'uuid' => fake()->uuid(), 'collection' => 'cover', 'status' => 'processing', 'original_disk' => 'local', 'original_path' => 'media/cover.jpg',
            'mime_type' => 'image/jpeg', 'size' => 1000, 'width' => 1600, 'height' => 1000, 'variants_disk' => 'public',
        ]);

        $this->actingAs(User::factory()->admin()->create())->get(route('admin.experiencias'))
            ->assertOk()
            ->assertSee('La foto se está procesando')
            ->assertDontSee('class="review-item__img" src=""', false);
    }

    #[Test]
    public function an_admin_sees_every_experience_with_its_bookings_and_reviews(): void
    {
        $admin = User::factory()->admin()->create();
        $date = ExperienceDate::factory()->create(['starts_at' => now()->addWeek(), 'capacity' => 8]);
        $experience = $date->experience;
        $experience->update(['title' => 'Cocina criolla moderna']);
        $guest = User::factory()->create(['name' => 'Lucía Paz']);
        $booking = app(BookingService::class)->request($guest, $date, 2);
        $experience->reviews()->create(['booking_id' => $booking->id, 'user_id' => $guest->id, 'rating' => 5, 'body' => 'Una noche hermosa.', 'published_at' => now()]);
        $experience->refreshRating();
        Experience::factory()->inReview()->create(['title' => 'Otra en revisión']);

        $this->actingAs($admin)->get(route('admin.experiencias'))
            ->assertOk()
            ->assertSee('Cocina criolla moderna')
            ->assertSee('1 próximas')
            ->assertSee('5,0 (1)');
        $this->actingAs($admin)->get(route('admin.experiencias', ['estado' => 'in_review']))
            ->assertSee('Otra en revisión')
            ->assertDontSee('Cocina criolla moderna');

        $this->actingAs($admin)->get(route('admin.experiencias.show', $experience))
            ->assertOk()
            ->assertSee($booking->code)
            ->assertSee('Lucía Paz')
            ->assertSee('Una noche hermosa.');
    }

    #[Test]
    public function a_paused_experience_is_hidden_and_takes_no_bookings_until_it_is_resumed(): void
    {
        $admin = User::factory()->admin()->create();
        $date = ExperienceDate::factory()->create(['starts_at' => now()->addWeek(), 'capacity' => 8]);
        $experience = $date->experience;

        $this->actingAs($admin)->post(route('admin.experiencias.pausar', $experience))->assertSessionHasErrors('reason');
        $this->actingAs($admin)->post(route('admin.experiencias.pausar', $experience), ['reason' => 'Denuncias de otros usuarios.'])->assertSessionHasNoErrors();

        $this->assertSame(ExperienceStatus::Paused, $experience->fresh()->status);
        Notification::assertSentTo($experience->host->user, ExperienceReviewedNotification::class, fn ($notification) => $notification->outcome === ExperienceReviewedNotification::PAUSED);
        $this->actingAs(User::factory()->create())->get(route('experiencias.show', $experience))->assertNotFound();
        $this->actingAs($experience->host->user)->get(route('anfitrion.panel'))->assertSee('Motivo: Denuncias de otros usuarios.');

        try {
            app(BookingService::class)->request(User::factory()->create(), $date, 1);
            $this->fail('Una experiencia pausada no debería aceptar reservas.');
        } catch (BookingException $e) {
            $this->assertSame('Esta experiencia no está recibiendo reservas.', $e->getMessage());
        }

        $this->actingAs($admin)->post(route('admin.experiencias.reactivar', $experience))->assertSessionHasNoErrors();
        $this->assertSame(ExperienceStatus::Published, $experience->fresh()->status);
        $this->assertNull($experience->fresh()->paused_reason);
    }

    #[Test]
    public function only_admins_can_moderate(): void
    {
        $experience = Experience::factory()->inReview()->create();
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('admin.experiencias'))->assertForbidden();
        $this->actingAs($user)->post(route('admin.experiencias.aprobar', $experience))->assertForbidden();
        $this->assertSame(ExperienceStatus::InReview, $experience->fresh()->status);
    }
}
