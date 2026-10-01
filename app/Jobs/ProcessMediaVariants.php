<?php

namespace App\Jobs;

use App\Models\Experience;
use App\Models\Media;
use App\Services\ImageService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Throwable;

/**
 * Genera las versiones optimizadas de una foto en segundo plano.
 */
class ProcessMediaVariants implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /** @var list<int> */
    public array $backoff = [5, 30, 120];

    public int $timeout = 120;

    public function __construct(public Media $media) {}

    /**
     * @return list<object>
     */
    public function middleware(): array
    {
        // Si se gira dos veces seguidas, las versiones se generan una después de la otra.
        return [(new WithoutOverlapping((string) $this->media->getKey()))->releaseAfter(10)->expireAfter(180)];
    }

    public function handle(ImageService $images): void
    {
        $images->generateVariants($this->media);

        // Con las versiones listas, la IA revisa la foto: la de perfil sola, la portada junto con su experiencia.
        $media = $this->media->fresh();
        if ($media?->collection === 'avatar') {
            ModerateContent::avatar($media);
        } elseif ($media?->collection === 'cover' && $media->mediable instanceof Experience) {
            ModerateContent::experience($media->mediable);
        }
    }

    public function failed(?Throwable $exception): void
    {
        $this->media->forceFill([
            'status' => $this->media->variants ? 'ready' : 'failed',
            'error' => str($exception?->getMessage() ?? 'Error desconocido')->limit(500)->toString(),
        ])->save();
    }
}
