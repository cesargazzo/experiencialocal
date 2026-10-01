<?php

namespace Tests\Feature;

use App\Models\Experience;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class LocaleTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function spanish_is_the_default_and_the_browser_language_is_used_the_first_time(): void
    {
        $this->get(route('home'))->assertOk()->assertSee('<html lang="es-AR">', false);

        $this->withHeader('Accept-Language', 'pt-BR,pt;q=0.9,en;q=0.8')->get(route('home'))
            ->assertSee('<html lang="pt-BR">', false);
        $this->withHeader('Accept-Language', 'de-DE,de;q=0.9')->get(route('home'))
            ->assertSee('<html lang="es-AR">', false);
    }

    #[Test]
    public function the_chosen_language_is_remembered_in_the_session_and_the_account(): void
    {
        $user = User::factory()->create(['locale' => 'es']);

        $this->actingAs($user)->from(route('home'))->post(route('idioma'), ['locale' => 'en'])->assertRedirect(route('home'));
        $this->assertSame('en', $user->fresh()->locale);
        $this->actingAs($user)->get(route('home'))->assertSee('<html lang="en">', false)->assertSee('Language');

        $this->post(route('idioma'), ['locale' => 'klingon'])->assertSessionHasErrors('locale');
    }

    #[Test]
    public function the_travelers_pages_are_translated_but_what_hosts_write_is_not(): void
    {
        $experience = Experience::factory()->create(['title' => 'Empanadas en el patio']);

        $this->withSession(['locale' => 'en'])->get(route('experiencias.show', $experience))->assertOk()
            ->assertSee('Empanadas en el patio')->assertSee('per person')->assertDontSee('por persona');
        $this->withSession(['locale' => 'pt_BR'])->get(route('home'))->assertOk()->assertSee('por pessoa');
    }
}
