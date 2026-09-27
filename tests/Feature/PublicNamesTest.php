<?php

namespace Tests\Feature;

use App\Enums\VerificationLevel;
use App\Models\Experience;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PublicNamesTest extends TestCase
{
    use RefreshDatabase;

    private function experienceByMarta(): Experience
    {
        $host = User::factory()->create(['first_name' => 'Marta', 'last_name' => 'Quiroga Ledesma']);
        $experience = Experience::factory()->create();
        $experience->host->update(['user_id' => $host->id, 'display_name' => 'Marta Quiroga Ledesma']);

        return $experience->fresh();
    }

    #[Test]
    public function the_full_name_is_kept_in_sync_and_old_single_names_are_split(): void
    {
        $user = User::factory()->create(['first_name' => ' Lucía ', 'last_name' => 'Paz Ortiz']);
        $this->assertSame('Lucía Paz Ortiz', $user->name);

        $legacy = User::factory()->create(['name' => 'Rosa María Díaz']);
        $this->assertSame(['Rosa', 'María Díaz'], [$legacy->first_name, $legacy->last_name]);
    }

    #[Test]
    public function visitors_and_unverified_accounts_see_only_the_first_name(): void
    {
        $experience = $this->experienceByMarta();

        $this->get(route('experiencias.show', $experience))->assertOk()->assertSee('Marta')->assertDontSee('Quiroga');
        $this->actingAs(User::factory()->level(VerificationLevel::Contact)->create())
            ->get(route('experiencias.show', $experience))->assertOk()->assertDontSee('Quiroga');
        $this->get(route('home'))->assertOk()->assertDontSee('Quiroga');
    }

    #[Test]
    public function verified_accounts_and_the_person_themselves_see_the_last_name(): void
    {
        $experience = $this->experienceByMarta();

        $this->actingAs(User::factory()->level(VerificationLevel::Document)->create())
            ->get(route('experiencias.show', $experience))->assertSee('Marta Quiroga Ledesma');
        $this->actingAs($experience->host->user)
            ->get(route('experiencias.show', $experience))->assertSee('Marta Quiroga Ledesma');
    }

    #[Test]
    public function the_profile_saves_first_and_last_name_separately(): void
    {
        $user = User::factory()->level(VerificationLevel::Contact)->create();

        $this->actingAs($user)->put(route('cuenta.perfil.update'), [
            'first_name' => 'Ana', 'last_name' => 'Molina', 'birth_date' => '1990-01-01', 'country_code' => 'UY', 'city' => 'Montevideo',
        ])->assertSessionHasNoErrors();

        $this->assertSame(['Ana', 'Molina', 'Ana Molina'], [$user->fresh()->first_name, $user->fresh()->last_name, $user->fresh()->name]);
        $this->actingAs($user)->put(route('cuenta.perfil.update'), ['first_name' => 'Ana', 'birth_date' => '1990-01-01', 'country_code' => 'UY', 'city' => 'Montevideo'])
            ->assertSessionHasErrors('last_name');
    }
}
