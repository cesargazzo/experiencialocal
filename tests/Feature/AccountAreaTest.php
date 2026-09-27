<?php

namespace Tests\Feature;

use App\Enums\VerificationLevel;
use App\Livewire\HostOnboarding;
use App\Models\Province;
use App\Models\User;
use Database\Seeders\CategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
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
            ->assertDontSee(route('admin.usuarios'), false);

        $this->actingAs(User::factory()->admin()->create())->get(route('home'))
            ->assertSee(route('admin.usuarios'), false);
    }

    #[Test]
    public function a_person_can_edit_their_name_until_their_document_is_validated(): void
    {
        $user = User::factory()->level(VerificationLevel::Contact)->create();

        $this->actingAs($user)->put(route('cuenta.perfil.update'), ['name' => 'Lucía Paz', 'birth_date' => '1990-01-01', ...$this->location()])->assertSessionHasNoErrors();
        $this->assertSame('Lucía Paz', $user->fresh()->name);

        $validated = User::factory()->level(VerificationLevel::Document)->create(['name' => 'Ana Molina']);
        $this->actingAs($validated)->get(route('cuenta.perfil'))->assertOk()->assertSee('quedó validado con tu documento');
        $this->actingAs($validated)->put(route('cuenta.perfil.update'), ['name' => 'Otro Nombre', ...$this->location()])->assertSessionHasErrors('name');
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

    #[Test]
    public function registration_requires_a_birth_date_and_the_minimum_age(): void
    {
        $data = [
            'name' => 'Ana Paz', 'email' => 'ana@example.com', 'phone' => '+54 380 4000000', 'nationality_code' => 'AR',
            'password' => 'Segura2026x', 'password_confirmation' => 'Segura2026x',
        ];

        $this->post(route('register'), $data)->assertSessionHasErrors('birth_date');
        $this->post(route('register'), [...$data, 'birth_date' => now()->subYears(17)->toDateString()])
            ->assertSessionHasErrors(['birth_date' => 'Tenés que tener al menos 18 años para usar Tinku.']);
        $this->assertGuest();

        $this->post(route('register'), [...$data, 'birth_date' => '1990-05-20'])->assertRedirect(route('verificacion'));
        $this->assertSame('1990-05-20', User::where('email', 'ana@example.com')->value('birth_date')->toDateString());
    }

    #[Test]
    public function the_birth_date_can_be_completed_once_but_not_changed_after_validation(): void
    {
        $withoutDate = User::factory()->level(VerificationLevel::Document)->create(['birth_date' => null]);
        $this->actingAs($withoutDate)->put(route('cuenta.perfil.update'), ['birth_date' => '1985-03-10', ...$this->location()])->assertSessionHasNoErrors();
        $this->assertSame('1985-03-10', $withoutDate->fresh()->birth_date->toDateString());

        $this->actingAs($withoutDate->fresh())->put(route('cuenta.perfil.update'), ['birth_date' => '1999-01-01', ...$this->location()])->assertSessionHasErrors('birth_date');
        $this->assertSame('1985-03-10', $withoutDate->fresh()->birth_date->toDateString());
    }

    #[Test]
    public function a_validated_person_can_still_update_where_they_live(): void
    {
        $user = User::factory()->level(VerificationLevel::Document)->create(['name' => 'Ana Molina']);
        $laRioja = Province::where('code', 'AR-F')->value('id');

        $this->actingAs($user)->get(route('cuenta.perfil'))->assertOk()
            ->assertSee('País de residencia')
            ->assertSeeInOrder(['Argentina', 'Países de la región', 'Bolivia', 'Uruguay', 'Resto del mundo', 'Alemania', 'Otro país'])
            ->assertSee('Provincia')
            ->assertSee('Ciudad');
        $this->actingAs($user)->put(route('cuenta.perfil.update'), ['country_code' => 'AR', 'province_id' => $laRioja, 'city' => 'Chilecito'])
            ->assertSessionHasNoErrors();

        $user->refresh();
        $this->assertSame([$laRioja, 'Chilecito', 'Ana Molina'], [$user->province_id, $user->city, $user->name]);
        $this->assertSame('Chilecito, La Rioja, Argentina', $user->locationLabel());
    }

    #[Test]
    public function the_province_must_belong_to_the_chosen_country(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->put(route('cuenta.perfil.update'), ['country_code' => 'AR', 'city' => 'Rosario'])
            ->assertSessionHasErrors(['province_id' => 'Elegí tu provincia.']);

        $this->actingAs($user)->put(route('cuenta.perfil.update'), [
            'country_code' => 'UY', 'province_id' => Province::where('code', 'AR-S')->value('id'), 'city' => 'Montevideo',
        ])->assertSessionHasNoErrors();

        $user->refresh();
        $this->assertSame(['UY', null, 'Montevideo'], [$user->country_code, $user->province_id, $user->city]);
    }

    #[Test]
    public function the_postal_code_is_optional_and_validated_by_country(): void
    {
        $user = User::factory()->create();
        $location = $this->location();

        $this->actingAs($user)->put(route('cuenta.perfil.update'), [...$location, 'postal_code' => ' f5360 abc '])->assertSessionHasNoErrors();
        $this->assertSame('F5360ABC', $user->fresh()->postal_code);

        $this->actingAs($user)->put(route('cuenta.perfil.update'), [...$location, 'postal_code' => '53'])->assertSessionHasErrors('postal_code');
        $this->assertSame('F5360ABC', $user->fresh()->postal_code);

        $this->actingAs($user)->put(route('cuenta.perfil.update'), [...$location, 'postal_code' => ''])->assertSessionHasNoErrors();
        $this->assertNull($user->fresh()->postal_code);

        $this->actingAs($user)->put(route('cuenta.perfil.update'), ['country_code' => 'UY', 'city' => 'Montevideo', 'postal_code' => '11200'])->assertSessionHasNoErrors();
        $this->assertSame('11200', $user->fresh()->postal_code);
    }

    #[Test]
    public function host_onboarding_starts_with_the_persons_province_and_city(): void
    {
        $mendoza = Province::where('code', 'AR-M')->value('id');
        $user = User::factory()->level(VerificationLevel::Document)->create(['province_id' => $mendoza, 'city' => 'Maipú']);

        Livewire::actingAs($user)->test(HostOnboarding::class)
            ->assertSet('province_id', $mendoza)
            ->assertSet('city', 'Maipú');
    }

    /** @return array{country_code: string, province_id: int, city: string} */
    private function location(): array
    {
        return ['country_code' => 'AR', 'province_id' => Province::where('code', 'AR-C')->value('id'), 'city' => 'Buenos Aires'];
    }
}
