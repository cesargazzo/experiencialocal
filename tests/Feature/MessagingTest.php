<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Enums\VerificationLevel;
use App\Livewire\ConversationThread;
use App\Models\Conversation;
use App\Models\Experience;
use App\Models\ExperienceDate;
use App\Models\Message;
use App\Models\SecurityEvent;
use App\Models\User;
use App\Notifications\NewMessageNotification;
use App\Services\BookingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MessagingTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function a_traveller_asks_the_host_before_booking_and_the_host_answers(): void
    {
        Notification::fake();
        $experience = Experience::factory()->create();
        $host = $experience->host->user;
        $guest = User::factory()->level(VerificationLevel::Contact)->create(['first_name' => 'Ana', 'last_name' => 'Paz']);

        $this->actingAs($guest)->get(route('experiencias.show', $experience))->assertSee('Preguntale a '.$host->first_name);
        $response = $this->actingAs($guest)->post(route('mensajes.iniciar', $experience));
        $conversation = Conversation::sole();
        $response->assertRedirect(route('mensajes.show', $conversation));

        Livewire::actingAs($guest)->test(ConversationThread::class, ['conversation' => $conversation])
            ->set('body', '¿Se puede ir con chicos de 8 años?')
            ->call('send')
            ->assertHasNoErrors()
            ->assertSee('¿Se puede ir con chicos de 8 años?');

        Notification::assertSentTo($host, NewMessageNotification::class);
        $this->assertSame(1, Conversation::unreadCountFor($host));

        Livewire::actingAs($host)->test(ConversationThread::class, ['conversation' => $conversation->fresh()])
            ->assertSee('¿Se puede ir con chicos de 8 años?')
            ->set('body', 'Sí, claro. Es un paseo tranquilo.')
            ->call('send');

        $this->assertSame(0, Conversation::unreadCountFor($host));
        $this->assertSame(1, Conversation::unreadCountFor($guest));
        $this->actingAs($guest)->get(route('mensajes'))->assertOk()->assertSee('Sí, claro. Es un paseo tranquilo.');
    }

    #[Test]
    public function messages_are_stored_encrypted_and_only_participants_can_read_them(): void
    {
        $conversation = Conversation::factory()->create();
        $conversation->messages()->create(['sender_id' => $conversation->guest_id, 'body' => 'Texto privado de prueba']);

        $raw = DB::table('messages')->value('body');
        $this->assertStringNotContainsString('Texto privado', $raw);
        $this->assertSame('Texto privado de prueba', Message::sole()->body);

        $this->actingAs(User::factory()->create())->get(route('mensajes.show', $conversation))->assertForbidden();
        $this->actingAs(User::factory()->admin()->create())->get(route('mensajes.show', $conversation))->assertForbidden();
        $this->actingAs($conversation->guest)->get(route('mensajes.show', $conversation))->assertOk();
    }

    #[Test]
    public function contact_details_are_hidden_until_there_is_a_confirmed_booking(): void
    {
        $date = ExperienceDate::factory()->create(['starts_at' => now()->addWeek(), 'capacity' => 8]);
        $guest = User::factory()->create();
        $conversation = Conversation::create(['experience_id' => $date->experience_id, 'guest_id' => $guest->id, 'host_user_id' => $date->experience->host->user_id]);

        Livewire::actingAs($guest)->test(ConversationThread::class, ['conversation' => $conversation])
            ->set('body', 'Escribime al 11 5555-4444, a ana.paz@gmail.com o a www.ana.com.ar o @anapaz')
            ->call('send')
            ->assertSet('notice', 'Ocultamos datos de contacto de tu mensaje. Cuando la reserva esté confirmada vas a poder compartirlos.');
        $first = Message::sole();
        $this->assertTrue($first->contact_redacted);
        $this->assertStringNotContainsString('5555', $first->body);
        $this->assertStringNotContainsString('gmail', $first->body);
        $this->assertStringNotContainsString('anapaz', $first->body);

        $booking = app(BookingService::class)->request($guest, $date, 2);
        $booking->forceFill(['status' => BookingStatus::Confirmed])->save();
        Livewire::actingAs($guest)->test(ConversationThread::class, ['conversation' => $conversation])
            ->set('body', 'Mi celular es 11 5555-4444')
            ->call('send');
        $this->assertSame('Mi celular es 11 5555-4444', Message::latest('id')->first()->body);
    }

    #[Test]
    public function the_host_cannot_open_a_conversation_with_themselves_and_unconfirmed_accounts_are_sent_to_verify(): void
    {
        $experience = Experience::factory()->create();

        $this->actingAs($experience->host->user)->post(route('mensajes.iniciar', $experience))->assertRedirect(route('mensajes'));
        $this->actingAs(User::factory()->level(VerificationLevel::None)->create())->post(route('mensajes.iniciar', $experience))->assertRedirect(route('verificacion'));
        $this->assertSame(0, Conversation::count());
    }

    #[Test]
    public function a_reported_message_reaches_the_team_and_reading_it_is_logged(): void
    {
        $conversation = Conversation::factory()->create();
        $offensive = $conversation->messages()->create(['sender_id' => $conversation->host_user_id, 'body' => 'Mensaje ofensivo']);

        Livewire::actingAs($conversation->guest)->test(ConversationThread::class, ['conversation' => $conversation])
            ->call('startReport', $offensive->id)
            ->set('reportReason', 'Me insultó')
            ->call('report')
            ->assertHasNoErrors();
        $this->assertNotNull($offensive->fresh()->reported_at);

        $admin = User::factory()->admin()->create();
        $this->actingAs($admin)->get(route('admin.denuncias'))->assertOk()->assertSee('Mensaje ofensivo')->assertSee('Me insultó');
        $this->assertTrue(SecurityEvent::where('type', 'admin.reports_viewed')->where('user_id', $admin->id)->exists());

        $this->actingAs($admin)->post(route('admin.denuncias.descartar', $offensive))->assertSessionHasNoErrors();
        $this->assertNull($offensive->fresh()->reported_at);
    }
}
