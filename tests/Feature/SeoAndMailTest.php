<?php

namespace Tests\Feature;

use App\Enums\VerificationType;
use App\Models\ExperienceDate;
use App\Models\User;
use App\Notifications\VerificationCodeNotification;
use App\Services\VerificationService;
use Database\Seeders\CategorySeeder;
use Database\Seeders\PlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SeoAndMailTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([PlanSeeder::class, CategorySeeder::class]);
    }

    #[Test]
    public function an_experience_page_has_share_metadata_and_event_structured_data(): void
    {
        config(['tinku.indexable' => true]);
        $date = ExperienceDate::factory()->create();
        $experience = $date->experience;
        $url = route('experiencias.show', $experience);

        $this->get($url)
            ->assertOk()
            ->assertSee('<link rel="canonical" href="'.$url.'">', false)
            ->assertSee('<meta property="og:title" content="'.e($experience->title).' en La Rioja">', false)
            ->assertSee('<meta property="og:url" content="'.$url.'">', false)
            ->assertSee('<meta property="og:image" content="', false)
            ->assertSee('<meta name="twitter:card" content="summary_large_image">', false)
            ->assertSee('<meta name="robots" content="index, follow, max-image-preview:large">', false)
            ->assertSee('"@type":"Event"', false)
            ->assertSee('"priceCurrency":"ARS"', false);
    }

    #[Test]
    public function private_pages_and_test_environments_are_not_indexed(): void
    {
        config(['tinku.indexable' => true]);
        $this->get(route('login'))->assertSee('<meta name="robots" content="noindex, nofollow">', false);

        config(['tinku.indexable' => false]);
        $this->get(route('home'))->assertSee('<meta name="robots" content="noindex, nofollow">', false);
        $this->get('/robots.txt')->assertOk()->assertSee("Disallow: /\n", false);
    }

    #[Test]
    public function robots_and_sitemap_expose_only_public_pages(): void
    {
        config(['tinku.indexable' => true]);
        $published = ExperienceDate::factory()->create()->experience;
        $draft = ExperienceDate::factory()->create()->experience;
        $draft->update(['status' => 'draft']);

        $this->get('/robots.txt')->assertOk()->assertSee('Disallow: /admin')->assertSee('Sitemap: '.route('sitemap'));

        $this->get('/sitemap.xml')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/xml; charset=UTF-8')
            ->assertSee(route('experiencias.show', $published), false)
            ->assertDontSee(route('experiencias.show', $draft), false);
    }

    #[Test]
    public function google_analytics_loads_only_when_configured(): void
    {
        $this->get(route('home'))->assertDontSee('googletagmanager.com');

        config(['services.google_analytics.id' => 'G-TINKU123']);
        $this->get(route('home'))->assertSee('googletagmanager.com/gtag/js?id=G-TINKU123', false);
    }

    #[Test]
    public function the_email_verification_code_is_sent_by_mail(): void
    {
        Notification::fake();
        $user = User::factory()->create();

        $verification = app(VerificationService::class)->submit($user, VerificationType::Email);

        Notification::assertSentTo($user, VerificationCodeNotification::class, function (VerificationCodeNotification $notification) use ($verification): bool {
            return $notification->code === $verification->result['demo_code'];
        });
    }
}
