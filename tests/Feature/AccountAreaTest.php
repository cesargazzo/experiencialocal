<?php

namespace Tests\Feature;

use App\Enums\VerificationLevel;
use App\Models\User;
use Database\Seeders\CategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AccountAreaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CategorySeeder::class);
    }

    #[Test]
    public function the_header_shows_a_profile_menu_with_name_and_account_links(): void
    {
        $user = User::factory()->create(['name' => 'Lucía Paz']);

        $this->actingAs($user)->get(route('home'))
            ->assertOk()
            ->assertSee('profile-menu__button', false)
            ->assertSee('Lucía')
            ->assertSee(route('cuenta.perfil'), false)
            ->assertSee(route('cuenta.seguridad'), false)
            ->assertDontSee(route('admin.verificaciones'), false);

        $this->actingAs(User::factory()->admin()->create())->get(route('home'))
            ->assertSee(route('admin.verificaciones'), false);
    }

    #[Test]
    public function a_person_can_edit_their_name_until_their_document_is_validated(): void
    {
        $user = User::factory()->level(VerificationLevel::Contact)->create();

        $this->actingAs($user)->put(route('cuenta.perfil.update'), ['name' => 'Lucía Paz'])->assertSessionHasNoErrors();
        $this->assertSame('Lucía Paz', $user->fresh()->name);

        $validated = User::factory()->level(VerificationLevel::Document)->create(['name' => 'Ana Molina']);
        $this->actingAs($validated)->get(route('cuenta.perfil'))->assertOk()->assertSee('quedó validado con tu documento');
        $this->actingAs($validated)->put(route('cuenta.perfil.update'), ['name' => 'Otro Nombre'])->assertForbidden();
        $this->assertSame('Ana Molina', $validated->fresh()->name);
    }

    #[Test]
    public function the_security_page_changes_the_password(): void
    {
        $user = User::factory()->create(['password' => 'Anterior2026x']);

        $this->actingAs($user)->get(route('cuenta.seguridad'))->assertOk()->assertSee('Contraseña');
        $this->actingAs($user)->put(route('cuenta.seguridad.update'), [
            'current_password' => 'Anterior2026x', 'password' => 'Nueva2026Clave', 'password_confirmation' => 'Nueva2026Clave',
        ])->assertSessionHasNoErrors();

        $this->assertNotNull($user->fresh()->password_changed_at);
    }

    #[Test]
    public function account_pages_require_login(): void
    {
        $this->get(route('cuenta.perfil'))->assertRedirect(route('login'));
        $this->get(route('cuenta.seguridad'))->assertRedirect(route('login'));
    }
}
