<?php

namespace Tests\Feature;

use App\Enums\VerificationLevel;
use App\Models\Experience;
use App\Models\User;
use Database\Seeders\CategorySeeder;
use Database\Seeders\PlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class WebRoutesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([PlanSeeder::class, CategorySeeder::class]);
    }

    #[Test]
    public function the_landing_lists_published_experiences_and_plans(): void
    {
        $published = Experience::factory()->create(['title' => 'Cena en el patio']);
        Experience::factory()->inReview()->create(['title' => 'Todavía no visible']);

        $this->get('/')
            ->assertOk()
            ->assertSee('Cena en el patio')
            ->assertDontSee('Todavía no visible')
            ->assertSee('Impulso');

        $this->get('/experiencias/'.$published->slug)->assertOk()->assertSee($published->host->display_name);
        $this->get('/experiencias/todavia-no-visible')->assertNotFound();
    }

    #[Test]
    public function host_onboarding_requires_a_validated_document(): void
    {
        $this->get('/anfitrion/registro')->assertRedirect('/ingresar');

        $this->actingAs(User::factory()->level(VerificationLevel::Contact)->create())
            ->get('/anfitrion/registro')
            ->assertRedirect('/verificacion');

        $this->actingAs(User::factory()->level(VerificationLevel::Document)->create())
            ->get('/anfitrion/registro')
            ->assertOk()
            ->assertSee('Tu perfil de anfitrión');
    }

    #[Test]
    public function admin_pages_are_only_for_admins(): void
    {
        $this->actingAs(User::factory()->create())->get('/admin/verificaciones')->assertForbidden();
        $this->actingAs(User::factory()->admin()->create())->get('/admin/verificaciones')->assertOk();
    }

    #[Test]
    public function registration_creates_the_account_and_sends_contact_codes(): void
    {
        $this->post('/registrarme', [
            'name' => 'Chiara Rossi', 'email' => 'chiara@example.com', 'phone' => '+39 333 1234567',
            'nationality_code' => 'IT', 'password' => 'secret1234', 'password_confirmation' => 'secret1234',
        ])->assertRedirect('/verificacion');

        $user = User::where('email', 'chiara@example.com')->firstOrFail();
        $this->assertSame('IT', $user->nationality_code);
        $this->assertSame(VerificationLevel::None, $user->verification_level);
        $this->assertCount(2, $user->verifications);
    }
}
