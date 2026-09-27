<?php

namespace Tests\Feature;

use App\Enums\ExperienceStatus;
use App\Jobs\NotifyInterestedUsers;
use App\Models\Category;
use App\Models\Experience;
use App\Models\ExperienceDate;
use App\Models\Province;
use App\Models\User;
use App\Notifications\ExperienceMatchNotification;
use Database\Seeders\CategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class InterestAlertsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CategorySeeder::class);
    }

    #[Test]
    public function a_person_saves_categories_provinces_and_alerts(): void
    {
        $user = User::factory()->create();
        $food = Category::where('slug', 'comida')->value('id');
        $laRioja = Province::where('code', 'AR-F')->value('id');

        $this->actingAs($user)->get(route('cuenta.intereses'))->assertOk()->assertSee('Qué te gusta hacer')->assertSee('Tierra del Fuego');
        $this->actingAs($user)->put(route('cuenta.intereses.update'), ['categories' => [$food], 'provinces' => [$laRioja], 'interest_alerts' => '1'])
            ->assertSessionHas('status', 'Guardamos tus intereses. Te avisamos cuando aparezca algo.');

        $this->assertSame([$food], $user->interestedCategories()->pluck('categories.id')->all());
        $this->assertSame([$laRioja], $user->interestedProvinces()->pluck('provinces.id')->all());
    }

    #[Test]
    public function publishing_an_experience_notifies_only_matching_people_once(): void
    {
        Notification::fake();
        $food = Category::where('slug', 'comida')->firstOrFail();
        $trips = Category::where('slug', 'paseo')->firstOrFail();
        $laRioja = Province::where('code', 'AR-F')->firstOrFail();
        $mendoza = Province::where('code', 'AR-M')->firstOrFail();

        $matchesBoth = User::factory()->create();
        $matchesBoth->interestedCategories()->sync([$food->id]);
        $matchesBoth->interestedProvinces()->sync([$laRioja->id]);
        $onlyProvince = User::factory()->create();
        $onlyProvince->interestedProvinces()->sync([$laRioja->id]);
        $otherCategory = User::factory()->create();
        $otherCategory->interestedCategories()->sync([$trips->id]);
        $otherProvince = User::factory()->create();
        $otherProvince->interestedProvinces()->sync([$mendoza->id]);
        $alertsOff = User::factory()->create(['interest_alerts' => false]);
        $alertsOff->interestedCategories()->sync([$food->id]);
        $noInterests = User::factory()->create();

        $experience = Experience::factory()->inReview()->create(['category_id' => $food->id, 'province_id' => $laRioja->id]);
        ExperienceDate::factory()->for($experience)->create();
        $experience->host->user->interestedCategories()->sync([$food->id]);

        $experience->update(['status' => ExperienceStatus::Published, 'published_at' => now()]);

        Notification::assertSentTo([$matchesBoth, $onlyProvince], ExperienceMatchNotification::class);
        Notification::assertNotSentTo([$otherCategory, $otherProvince, $alertsOff, $noInterests, $experience->host->user], ExperienceMatchNotification::class);

        // Otra vez en la misma semana: no se repite.
        (new NotifyInterestedUsers($experience))->handle();
        Notification::assertSentToTimes($matchesBoth, ExperienceMatchNotification::class, 1);
    }

    #[Test]
    public function a_new_date_on_a_published_experience_triggers_an_availability_alert(): void
    {
        Queue::fake();
        $experience = Experience::factory()->create(['published_at' => now()->subDay()]);
        Queue::assertPushed(NotifyInterestedUsers::class, 1);

        ExperienceDate::factory()->for($experience)->create();

        Queue::assertPushed(NotifyInterestedUsers::class, fn (NotifyInterestedUsers $job) => $job->reason === 'new_date');
    }
}
