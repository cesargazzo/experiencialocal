<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\ResetPasswordNotification;
use Database\Seeders\CategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CategorySeeder::class);
    }

    #[Test]
    public function the_login_page_links_to_password_recovery(): void
    {
        $this->get(route('login'))->assertOk()->assertSee(route('password.request'), false);
        $this->get(route('password.request'))->assertOk()->assertSee('Recuperá tu');
    }

    #[Test]
    public function it_sends_a_reset_link_without_revealing_which_emails_exist(): void
    {
        Notification::fake();
        $user = User::factory()->create(['email' => 'lucia@example.com']);
        $message = 'Si hay una cuenta con ese email, te mandamos un enlace para elegir una contraseña nueva.';

        $this->post(route('password.email'), ['email' => 'lucia@example.com'])->assertSessionHas('status', $message);
        $this->post(route('password.email'), ['email' => 'nadie@example.com'])->assertSessionHas('status', $message);

        Notification::assertSentTo($user, ResetPasswordNotification::class);
        Notification::assertSentTimes(ResetPasswordNotification::class, 1);
    }

    #[Test]
    public function a_valid_link_sets_a_new_password_that_follows_the_policy(): void
    {
        $user = User::factory()->create(['password' => 'Anterior2026x', 'must_change_password' => true]);
        $token = Password::createToken($user);

        $this->get(route('password.reset', ['token' => $token, 'email' => $user->email]))->assertOk()->assertSee($user->email);

        $this->post(route('password.update'), [
            'token' => $token, 'email' => $user->email, 'password' => 'corta', 'password_confirmation' => 'corta',
        ])->assertSessionHasErrors('password');

        $this->post(route('password.update'), [
            'token' => $token, 'email' => $user->email, 'password' => 'NuevaClave2026', 'password_confirmation' => 'NuevaClave2026',
        ])->assertRedirect(route('login'));

        $user->refresh();
        $this->assertTrue(Hash::check('NuevaClave2026', $user->password));
        $this->assertFalse($user->must_change_password);
        $this->assertNotNull($user->password_changed_at);

        // El enlace sirve una sola vez.
        $this->post(route('password.update'), [
            'token' => $token, 'email' => $user->email, 'password' => 'OtraClave2026x', 'password_confirmation' => 'OtraClave2026x',
        ])->assertSessionHasErrors('email');
    }

    #[Test]
    public function an_invalid_link_is_rejected(): void
    {
        $user = User::factory()->create(['password' => 'Anterior2026x']);

        $this->post(route('password.update'), [
            'token' => 'inventado', 'email' => $user->email, 'password' => 'NuevaClave2026', 'password_confirmation' => 'NuevaClave2026',
        ])->assertSessionHasErrors(['email' => 'El enlace no es válido o venció. Pedí uno nuevo.']);

        $this->assertTrue(Hash::check('Anterior2026x', $user->fresh()->password));
    }
}
