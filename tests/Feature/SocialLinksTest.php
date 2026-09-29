<?php

namespace Tests\Feature;

use App\Enums\SocialNetwork;
use App\Enums\VerificationLevel;
use App\Models\Experience;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SocialLinksTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function handles_and_profile_links_become_the_official_address_and_anything_else_is_rejected(): void
    {
        $this->assertSame('https://www.instagram.com/marta.rioja', SocialNetwork::Instagram->normalize('@marta.rioja'));
        $this->assertSame('https://www.instagram.com/marta.rioja', SocialNetwork::Instagram->normalize('https://instagram.com/marta.rioja/?hl=es'));
        $this->assertSame('https://x.com/marta', SocialNetwork::X->normalize('twitter.com/marta'));
        $this->assertSame('https://www.linkedin.com/in/marta-quiroga', SocialNetwork::LinkedIn->normalize('https://ar.linkedin.com/in/marta-quiroga/'));
        $this->assertSame('https://tusitio.com.ar', SocialNetwork::Website->normalize('tusitio.com.ar'));

        $this->assertNull(SocialNetwork::Instagram->normalize('https://instagram.com.phishing.io/marta'));
        $this->assertNull(SocialNetwork::Instagram->normalize('<script>'));
        $this->assertNull(SocialNetwork::Website->normalize('javascript:alert(1)'));
        $this->assertNull(SocialNetwork::Website->normalize('https://usuario:clave@banco.com'));
        $this->assertNull(SocialNetwork::Website->normalize('localhost'));
    }

    #[Test]
    public function a_person_saves_their_networks_and_chooses_which_are_public(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->put(route('cuenta.redes.update'), ['social' => [
            'instagram' => ['url' => '@marta.rioja', 'public' => '1'],
            'web' => ['url' => 'caminatasrioja.com.ar'],
        ]])->assertRedirect(route('cuenta.perfil').'#redes');

        $this->assertEquals([
            'instagram' => ['url' => 'https://www.instagram.com/marta.rioja', 'public' => true],
            'web' => ['url' => 'https://caminatasrioja.com.ar', 'public' => false],
        ], $user->fresh()->social_links);

        $this->actingAs($user)->put(route('cuenta.redes.update'), ['social' => ['tiktok' => ['url' => 'https://evil.com/@marta']]])
            ->assertSessionHasErrors('social.tiktok.url');
        $this->assertArrayNotHasKey('tiktok', $user->fresh()->social_links);
    }

    #[Test]
    public function public_networks_follow_the_last_name_rule_and_private_ones_are_never_shown(): void
    {
        $host = User::factory()->create(['social_links' => [
            'instagram' => ['url' => 'https://www.instagram.com/marta.rioja', 'public' => true],
            'web' => ['url' => 'https://marta-privada.com.ar', 'public' => false],
        ]]);
        $experience = Experience::factory()->create();
        $experience->host->update(['user_id' => $host->id]);

        $this->get(route('experiencias.show', $experience))->assertOk()->assertDontSee('instagram.com/marta.rioja');
        $this->actingAs(User::factory()->level(VerificationLevel::Contact)->create())
            ->get(route('experiencias.show', $experience))->assertDontSee('instagram.com/marta.rioja');

        $this->actingAs(User::factory()->level(VerificationLevel::Document)->create())
            ->get(route('experiencias.show', $experience))
            ->assertSee('href="https://www.instagram.com/marta.rioja"', false)
            ->assertSee('rel="nofollow noopener noreferrer ugc"', false)
            ->assertDontSee('marta-privada.com.ar');

        $this->actingAs(User::factory()->admin()->create())->get(route('admin.usuarios.show', $host))
            ->assertSee('marta-privada.com.ar')->assertSee('(privada)');
    }
}
