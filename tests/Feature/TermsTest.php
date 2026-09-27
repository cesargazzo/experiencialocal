<?php

namespace Tests\Feature;

use App\Models\TermsAcceptance;
use App\Models\TermsVersion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LogicException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TermsTest extends TestCase
{
    use RefreshDatabase;

    /** @return array<string, string> */
    private function registration(): array
    {
        return [
            'first_name' => 'Ana', 'last_name' => 'Paz', 'email' => 'ana@example.com', 'phone' => '+54 380 4000000', 'nationality_code' => 'AR',
            'birth_date' => '1990-05-20', 'password' => 'Segura2026x', 'password_confirmation' => 'Segura2026x',
        ];
    }

    #[Test]
    public function signing_up_requires_accepting_the_current_terms_and_records_who_signed(): void
    {
        $terms = TermsVersion::factory()->published()->create(['version' => '1.0']);

        $this->get(route('register'))->assertSee('términos y condiciones')->assertSee('versión 1.0');
        $this->post(route('register'), [...$this->registration(), 'terms_version_id' => $terms->id])
            ->assertSessionHasErrors(['accept_terms' => 'Para crear tu cuenta tenés que aceptar los términos y condiciones.']);
        $this->assertGuest();

        $this->post(route('register'), [...$this->registration(), 'terms_version_id' => $terms->id, 'accept_terms' => '1'])->assertRedirect();

        $acceptance = TermsAcceptance::sole();
        $this->assertSame(User::where('email', 'ana@example.com')->value('id'), $acceptance->user_id);
        $this->assertSame($terms->id, $acceptance->terms_version_id);
        $this->assertSame('register', $acceptance->context);
        $this->assertNotNull($acceptance->ip);
    }

    #[Test]
    public function a_new_version_that_requires_it_must_be_accepted_before_going_on(): void
    {
        $first = TermsVersion::factory()->published()->create(['version' => '1.0', 'published_at' => now()->subMonth()]);
        $user = User::factory()->create();
        TermsAcceptance::create(['user_id' => $user->id, 'terms_version_id' => $first->id, 'accepted_at' => now()->subMonth(), 'context' => 'register']);

        $this->actingAs($user)->get(route('cuenta.perfil'))->assertOk();

        $second = TermsVersion::factory()->published()->create(['version' => '2.0', 'changes_summary' => 'Sumamos la política de cancelaciones.']);

        $this->actingAs($user)->get(route('cuenta.perfil'))->assertRedirect(route('terminos.aceptar'));
        $this->actingAs($user)->get(route('terminos.aceptar'))->assertOk()->assertSee('Actualizamos los')->assertSee('Sumamos la política de cancelaciones.');
        $this->actingAs($user)->post(route('terminos.aceptar.store'), ['terms_version_id' => $second->id])->assertSessionHasErrors('accept');

        $this->actingAs($user)->post(route('terminos.aceptar.store'), ['terms_version_id' => $second->id, 'accept' => '1'])
            ->assertRedirect(route('cuenta.perfil'));
        $this->actingAs($user)->get(route('cuenta.perfil'))->assertOk();
        $this->assertSame(2, $user->termsAcceptances()->count());
    }

    #[Test]
    public function a_minor_version_does_not_ask_to_accept_again(): void
    {
        $first = TermsVersion::factory()->published()->create(['version' => '1.0', 'published_at' => now()->subMonth()]);
        $user = User::factory()->create();
        TermsAcceptance::create(['user_id' => $user->id, 'terms_version_id' => $first->id, 'accepted_at' => now()->subMonth(), 'context' => 'register']);
        TermsVersion::factory()->published()->create(['version' => '1.1', 'requires_reacceptance' => false]);

        $this->actingAs($user)->get(route('cuenta.perfil'))->assertOk();
        $this->get(route('terminos'))->assertOk()->assertSee('Versión 1.1');
    }

    #[Test]
    public function admins_draft_edit_and_publish_versions_and_published_ones_are_frozen(): void
    {
        $admin = User::factory()->admin()->create();
        $body = str_repeat('Estas son las reglas para usar Tinku con respeto y seguridad. ', 5);

        $this->actingAs($admin)->post(route('admin.terminos.store'), ['version' => '1.0', 'title' => 'Términos de Tinku', 'body' => $body, 'requires_reacceptance' => '1'])
            ->assertRedirect();
        $terms = TermsVersion::sole();
        $this->assertFalse($terms->isPublished());

        // Mientras no hay versión publicada, nadie queda bloqueado.
        $this->actingAs($admin)->get(route('admin.terminos'))->assertOk()->assertSee('Borrador');

        $this->actingAs($admin)->post(route('admin.terminos.publicar', $terms))->assertRedirect(route('admin.terminos'));
        $terms->refresh();
        $this->assertTrue($terms->isPublished());
        $this->assertSame(hash('sha256', $terms->body), $terms->body_hash);

        // El admin también tiene que aceptarla.
        $this->actingAs($admin)->get(route('admin.usuarios'))->assertRedirect(route('terminos.aceptar'));
        $this->actingAs($admin)->post(route('terminos.aceptar.store'), ['terms_version_id' => $terms->id, 'accept' => '1']);

        $this->actingAs($admin)->put(route('admin.terminos.update', $terms), ['version' => '1.0', 'title' => 'Otro', 'body' => $body])->assertStatus(409);
        $this->actingAs($admin)->get(route('admin.terminos.aceptaciones', $terms))->assertOk()->assertSee($admin->email)->assertSee('Al ingresar');
    }

    #[Test]
    public function an_acceptance_cannot_be_modified(): void
    {
        $terms = TermsVersion::factory()->published()->create();
        $acceptance = TermsAcceptance::create(['user_id' => User::factory()->create()->id, 'terms_version_id' => $terms->id, 'accepted_at' => now(), 'context' => 'register']);

        $this->expectException(LogicException::class);
        $acceptance->update(['ip' => '1.2.3.4']);
    }
}
