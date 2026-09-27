<?php

namespace Tests\Feature;

use App\Enums\VerificationLevel;
use App\Models\Invitation;
use App\Models\User;
use App\Notifications\InvitationNotification;
use Database\Seeders\CategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class InvitationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CategorySeeder::class);
    }

    /**
     * @return array<string, string>
     */
    private function registration(string $email): array
    {
        return [
            'first_name' => 'Nueva', 'last_name' => 'Persona', 'email' => $email, 'phone' => '+54 380 4000000', 'birth_date' => '1992-02-02',
            'nationality_code' => 'AR', 'password' => 'Segura2026x', 'password_confirmation' => 'Segura2026x',
        ];
    }

    #[Test]
    public function an_email_invitation_is_sent_and_prefills_registration(): void
    {
        Notification::fake();
        $inviter = User::factory()->create(['name' => 'Marta Quiroga']);

        $this->actingAs($inviter)
            ->post(route('cuenta.invitaciones.email'), ['name' => 'Rosa Díaz', 'email' => 'Rosa@Example.com'])
            ->assertSessionHas('status', 'Listo, le mandamos la invitación a Rosa Díaz.');

        $url = null;
        Notification::assertSentTo(new AnonymousNotifiable, InvitationNotification::class, function ($notification, $channels, $notifiable) use (&$url) {
            $url = $notification->url;

            return $notifiable->routes['mail'] === ['rosa@example.com' => 'Rosa Díaz'];
        });
        auth()->logout();

        $this->get($url)->assertRedirect(route('register'));
        $this->get(route('register'))->assertSee('Marta')->assertDontSee('Quiroga')->assertSee('value="Rosa"', false)->assertSee('value="Díaz"', false)->assertSee('value="rosa@example.com"', false);

        $this->post(route('register'), $this->registration('rosa@example.com'))->assertRedirect(route('verificacion'));

        $rosa = User::where('email', 'rosa@example.com')->firstOrFail();
        $this->assertTrue($rosa->invitedBy->is($inviter));
        $this->assertNotNull(Invitation::firstOrFail()->accepted_at);

        // Sirve una sola vez.
        auth()->logout();
        $this->get($url)->assertRedirect(route('register'))->assertSessionHas('status', 'Esa invitación ya se usó o venció. Igual podés crear tu cuenta.');
    }

    #[Test]
    public function a_shareable_link_is_shown_once_with_a_whatsapp_button(): void
    {
        $inviter = User::factory()->create();

        $response = $this->actingAs($inviter)->from(route('cuenta.invitaciones'))->followingRedirects()->post(route('cuenta.invitaciones.enlace'), ['name' => 'Juan']);

        $response->assertOk()->assertSee('Tu enlace está listo')->assertSee('https://wa.me/?text=', false)->assertSee('Hola%2C%20Juan.', false);
        $invitation = Invitation::firstOrFail();
        $this->assertSame('link', $invitation->channel);
        $this->assertSame(64, strlen($invitation->token_hash), 'Solo se guarda el hash del token.');

        $this->actingAs($inviter)->get(route('cuenta.invitaciones'))->assertDontSee('Tu enlace está listo');
    }

    #[Test]
    public function inviting_does_not_reveal_existing_accounts_and_respects_the_daily_limit(): void
    {
        Notification::fake();
        config(['tinku.invitations.daily_limit' => 2]);
        User::factory()->create(['email' => 'ya@example.com']);
        $inviter = User::factory()->create();

        $this->actingAs($inviter)->post(route('cuenta.invitaciones.email'), ['name' => 'Ya Está', 'email' => 'ya@example.com'])
            ->assertSessionHas('status', 'Listo, le mandamos la invitación a Ya Está.');
        Notification::assertNothingSent();

        $this->actingAs($inviter)->post(route('cuenta.invitaciones.enlace'));
        $this->actingAs($inviter)->post(route('cuenta.invitaciones.enlace'))->assertSessionHasErrorsIn('link', 'name');
        $this->assertSame(2, Invitation::count());
    }

    #[Test]
    public function only_accounts_with_confirmed_contact_can_invite(): void
    {
        $this->actingAs(User::factory()->level(VerificationLevel::None)->create())
            ->get(route('cuenta.invitaciones'))
            ->assertRedirect(route('verificacion'));
    }

    #[Test]
    public function expired_invitations_are_rejected(): void
    {
        $invitation = Invitation::issue(User::factory()->create(), ['channel' => 'link']);
        $this->travel(config('tinku.invitations.valid_days') + 1)->days();

        $this->get($invitation->url())->assertSessionHas('status', 'Esa invitación ya se usó o venció. Igual podés crear tu cuenta.');
        $this->assertNull(session('invitation_token'));
    }
}
