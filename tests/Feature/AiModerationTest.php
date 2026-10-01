<?php

namespace Tests\Feature;

use App\Enums\ExperienceStatus;
use App\Enums\ModerationVerdict;
use App\Enums\TeamRole;
use App\Enums\VerificationLevel;
use App\Jobs\ModerateContent;
use App\Livewire\ConversationThread;
use App\Models\Conversation;
use App\Models\Experience;
use App\Models\Media;
use App\Models\Message;
use App\Models\ModerationReview;
use App\Models\User;
use App\Services\Moderation\ContentModerator;
use App\Services\Moderation\ModerationResult;
use App\Services\SecurityLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AiModerationTest extends TestCase
{
    use RefreshDatabase;

    /** @var list<array{kind: string, fields: array<string, string|null>, image: bool}> */
    private array $reviewed = [];

    private function fakeModerator(ModerationVerdict $verdict, string $reason = 'Motivo de prueba'): void
    {
        config(['tinku.moderation.enabled' => true, 'services.anthropic.key' => 'test-key']);
        $this->app->instance(ContentModerator::class, new class($verdict, $reason, $this->reviewed) implements ContentModerator
        {
            public function __construct(private ModerationVerdict $verdict, private string $reason, private array &$log) {}

            public function review(string $kind, array $fields, ?array $image = null): ModerationResult
            {
                $this->log[] = ['kind' => $kind, 'fields' => $fields, 'image' => $image !== null];

                return new ModerationResult($this->verdict, $this->verdict === ModerationVerdict::Allow ? [] : ['estafa'], $this->reason, 'claude-opus-5-5');
            }
        });
    }

    private function conversation(): Conversation
    {
        $experience = Experience::factory()->create();
        $guest = User::factory()->level(VerificationLevel::Contact)->create();

        return Conversation::create(['experience_id' => $experience->id, 'guest_id' => $guest->id, 'host_user_id' => $experience->host->user_id]);
    }

    #[Test]
    public function it_is_off_until_turned_on_with_a_key(): void
    {
        $this->assertFalse(ModerateContent::enabled());

        Experience::factory()->create(['status' => ExperienceStatus::InReview]);
        $this->assertSame(0, ModerationReview::count());
    }

    #[Test]
    public function a_blocked_message_is_held_back_and_reaches_the_reports_queue(): void
    {
        $this->fakeModerator(ModerationVerdict::Block, 'Pide una seña por transferencia fuera de Tinku.');
        $conversation = $this->conversation();
        $guest = User::find($conversation->guest_id);
        $host = User::find($conversation->host_user_id);

        Livewire::actingAs($guest)->test(ConversationThread::class, ['conversation' => $conversation])
            ->set('body', 'Pasame una seña al alias tinku.reserva y te guardo el lugar')->call('send');

        $message = Message::sole();
        $this->assertNotNull($message->hidden_at);
        $this->assertNotNull($message->reported_at);
        $this->assertStringContainsString('Revisión automática', $message->report_reason);
        $this->assertSame('mensaje entre un viajero y un anfitrión', $this->reviewed[0]['kind']);

        Livewire::actingAs($host)->test(ConversationThread::class, ['conversation' => $conversation->fresh()])
            ->assertDontSee('tinku.reserva')->assertSee('Retuvimos este mensaje');
        Livewire::actingAs($guest)->test(ConversationThread::class, ['conversation' => $conversation->fresh()])
            ->assertSee('tinku.reserva')->assertSee('la otra persona todavía no lo ve');

        $moderator = User::factory()->team(TeamRole::Moderator)->create();
        $this->actingAs($moderator)->get(route('admin.denuncias'))
            ->assertSee('Marcado por la revisión automática')->assertSee('Retenido');

        $this->actingAs($moderator)->post(route('admin.denuncias.descartar', $message));
        $this->assertNull($message->fresh()->hidden_at);
    }

    #[Test]
    public function a_doubtful_message_stays_visible_but_is_reported(): void
    {
        $this->fakeModerator(ModerationVerdict::Review);
        $conversation = $this->conversation();

        Livewire::actingAs(User::find($conversation->guest_id))->test(ConversationThread::class, ['conversation' => $conversation])
            ->set('body', 'Buscame en insta como arroba marta punto rioja')->call('send');

        $message = Message::sole();
        $this->assertNull($message->hidden_at);
        $this->assertNotNull($message->reported_at);
    }

    #[Test]
    public function an_experience_in_review_gets_a_verdict_the_team_sees_but_still_decides(): void
    {
        $this->fakeModerator(ModerationVerdict::Review, 'La descripción promete transporte que no figura en el precio.');

        $experience = Experience::factory()->create(['status' => ExperienceStatus::InReview, 'approved_at' => null]);

        $review = $experience->latestModeration()->first();
        $this->assertSame(ModerationVerdict::Review, $review->verdict);
        $this->assertSame($experience->title, $this->reviewed[0]['fields']['title']);
        $this->assertSame(ExperienceStatus::InReview, $experience->fresh()->status);

        $this->actingAs(User::factory()->team(TeamRole::Moderator)->create())->get(route('admin.experiencias'))
            ->assertSee('IA: revisar')->assertSee('La descripción promete transporte');

        // Publicada o con cambios que no son de texto: no se vuelve a revisar.
        $experience->update(['price' => 99000]);
        $this->assertCount(1, $this->reviewed);
    }

    #[Test]
    public function an_unacceptable_profile_photo_is_hidden(): void
    {
        $this->fakeModerator(ModerationVerdict::Block);
        $user = User::factory()->create();
        $media = $user->morphMany(Media::class, 'mediable')->create([
            'uuid' => (string) str()->uuid(), 'collection' => 'avatar', 'status' => 'ready', 'rotation' => 0,
            'original_disk' => 'local', 'original_path' => 'x.jpg', 'mime_type' => 'image/jpeg', 'size' => 1, 'width' => 400, 'height' => 400,
            'variants_disk' => 'public', 'variants' => ['md' => ['path' => 'media/avatar/x/md.webp', 'width' => 320, 'height' => 320, 'size' => 1]],
        ]);

        (new ModerateContent($media))->handle(app(ContentModerator::class), app(SecurityLog::class));

        $this->assertSame('rejected', $media->fresh()->status);
        $this->assertNull($user->fresh()->avatar);
    }
}
