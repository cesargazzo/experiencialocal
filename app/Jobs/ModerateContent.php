<?php

namespace App\Jobs;

use App\Enums\ExperienceStatus;
use App\Enums\ModerationVerdict;
use App\Models\Experience;
use App\Models\Media;
use App\Models\Message;
use App\Services\Moderation\ContentModerator;
use App\Services\Moderation\ModerationResult;
use App\Services\SecurityLog;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;

/**
 * Revisión automática con IA en segundo plano. Las experiencias quedan marcadas para el
 * equipo (la decisión sigue siendo humana); los mensajes graves se retienen y se denuncian
 * solos; las fotos de perfil o de galería inaceptables se ocultan.
 */
class ModerateContent implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /** @var list<int> */
    public array $backoff = [30, 120, 600];

    public int $timeout = 120;

    public function __construct(public Experience|Message|Media $subject) {}

    public static function enabled(): bool
    {
        return config('tinku.moderation.enabled') && filled(config('services.anthropic.key'));
    }

    public static function experience(Experience $experience): void
    {
        self::dispatchIfEnabled($experience);
    }

    public static function message(Message $message): void
    {
        self::dispatchIfEnabled($message);
    }

    /** Foto de perfil o de la galería de una experiencia. */
    public static function photo(Media $media): void
    {
        self::dispatchIfEnabled($media);
    }

    private static function dispatchIfEnabled(Model $subject): void
    {
        if (self::enabled()) {
            self::dispatch($subject)->afterCommit();
        }
    }

    public function handle(ContentModerator $moderator, SecurityLog $securityLog): void
    {
        $subject = $this->subject->fresh();
        if ($subject === null) {
            return;
        }

        $result = match (true) {
            $subject instanceof Experience => $this->reviewExperience($subject, $moderator),
            $subject instanceof Message => $moderator->review('mensaje entre un viajero y un anfitrión', ['mensaje' => $subject->body]),
            $subject instanceof Media => $subject->collection === 'avatar'
                ? $moderator->review('foto de perfil de una persona', [], $this->image($subject, 'md'))
                : $moderator->review('foto de la galería de una experiencia', ['experiencia' => $subject->mediable?->title], $this->image($subject, 'thumb')),
        };
        if ($result === null) {
            return;
        }

        $subject->moderationReviews()->create([
            'verdict' => $result->verdict, 'categories' => $result->categories, 'reason' => $result->reason, 'model' => $result->model,
        ]);

        if ($result->verdict !== ModerationVerdict::Allow) {
            $this->act($subject, $result, $securityLog);
        }
    }

    private function reviewExperience(Experience $experience, ContentModerator $moderator): ?ModerationResult
    {
        // Si ya se aprobó, se publicó o se pausó mientras esperaba, no hace falta.
        if ($experience->status !== ExperienceStatus::InReview) {
            return null;
        }

        return $moderator->review(
            'experiencia publicada por un anfitrión',
            collect(Experience::MODERATED_FIELDS)->mapWithKeys(fn (string $field) => [$field => $experience->{$field}])->all(),
            $experience->cover ? $this->image($experience->cover, 'card') : null,
        );
    }

    /**
     * @return array{data: string, media_type: string}|null
     */
    private function image(Media $media, string $variant): ?array
    {
        $path = $media->variants[$variant]['path'] ?? null;
        $binary = $path ? Storage::disk($media->variants_disk)->get($path) : null;
        if (! $binary) {
            return null;
        }

        return ['data' => base64_encode($binary), 'media_type' => str_ends_with($path, '.jpg') ? 'image/jpeg' : 'image/webp'];
    }

    private function act(Experience|Message|Media $subject, ModerationResult $result, SecurityLog $securityLog): void
    {
        if ($subject instanceof Message) {
            // Lo grave se retiene; lo dudoso queda visible pero llega a Denuncias.
            $subject->forceFill([
                'reported_at' => $subject->reported_at ?? now(),
                'report_reason' => $subject->report_reason ?? 'Revisión automática: '.$result->reason,
                'hidden_at' => $result->verdict === ModerationVerdict::Block ? now() : $subject->hidden_at,
            ])->save();
        }

        if ($subject instanceof Media && $result->verdict === ModerationVerdict::Block) {
            $subject->forceFill(['status' => 'rejected', 'error' => 'Revisión automática: '.$result->reason])->save();
        }

        $securityLog->record('moderation.flagged', null, [
            'contenido' => class_basename($subject).' #'.$subject->getKey(),
            'veredicto' => $result->verdict->value,
            'categorias' => $result->categories,
        ], null, $result->verdict === ModerationVerdict::Block ? 'warning' : 'info');
    }
}
