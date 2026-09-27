<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\PasswordPolicy;
use Database\Seeders\CategorySeeder;
use Database\Seeders\PlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PasswordPolicyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([PlanSeeder::class, CategorySeeder::class]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function registration(string $password, array $overrides = []): array
    {
        return [
            'name' => 'Ana Paz', 'email' => 'ana@example.com', 'phone' => '+54 380 4000000',
            'nationality_code' => 'AR', 'password' => $password, 'password_confirmation' => $password,
            ...$overrides,
        ];
    }

    #[Test]
    public function registration_follows_the_policy_an_admin_configures(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->put(route('admin.contrasenas.update'), [
                'min_length' => 12, 'max_login_attempts' => 5, 'lockout_minutes' => 15,
                'require_mixed_case' => '1', 'require_numbers' => '1', 'require_symbols' => '1',
            ])
            ->assertSessionHasNoErrors();
        auth()->logout();

        $this->post(route('register'), $this->registration('Tinku2026Abc'))->assertSessionHasErrors('password');
        $this->assertGuest();

        $this->post(route('register'), $this->registration('Tinku!2026Abc'))->assertRedirect(route('verificacion'));
        $this->assertAuthenticated();
    }

    #[Test]
    public function the_minimum_length_can_never_go_below_the_floor(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->put(route('admin.contrasenas.update'), ['min_length' => 6, 'max_login_attempts' => 5, 'lockout_minutes' => 15])
            ->assertSessionHasErrors('min_length');

        $this->assertSame(PasswordPolicy::ABSOLUTE_MIN_LENGTH, PasswordPolicy::fromArray(['min_length' => 4])->minLength);
    }

    #[Test]
    public function only_admins_can_change_the_policy(): void
    {
        $this->actingAs(User::factory()->create())->get(route('admin.contrasenas'))->assertForbidden();
        $this->actingAs(User::factory()->create())->put(route('admin.contrasenas.update'), ['min_length' => 20])->assertForbidden();
    }

    #[Test]
    public function login_locks_after_the_configured_number_of_failed_attempts(): void
    {
        $user = User::factory()->create(['password' => 'Correcta2026']);
        $this->actingAs(User::factory()->admin()->create())
            ->put(route('admin.contrasenas.update'), ['min_length' => 10, 'max_login_attempts' => 3, 'lockout_minutes' => 10]);
        auth()->logout();

        foreach (range(1, 3) as $attempt) {
            $this->post(route('login'), ['email' => $user->email, 'password' => 'incorrecta'])->assertSessionHasErrors('email');
        }

        $this->post(route('login'), ['email' => $user->email, 'password' => 'Correcta2026'])
            ->assertSessionHasErrors(['email' => 'Hubo demasiados intentos. Probá de nuevo en 10 minutos.']);
        $this->assertGuest();
    }

    #[Test]
    public function password_fields_offer_show_and_hide(): void
    {
        $this->get(route('register'))
            ->assertOk()
            ->assertSee('aria-label="Mostrar contraseña"', false)
            ->assertSee('Al menos 10 caracteres');
    }
}
