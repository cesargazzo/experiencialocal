<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Storage;

/**
 * Una imagen subida: el original privado y sus versiones optimizadas.
 */
#[Fillable([
    'uuid', 'collection', 'status', 'rotation', 'original_disk', 'original_path', 'original_name', 'mime_type', 'size',
    'width', 'height', 'variants_disk', 'variants', 'alt', 'error',
])]
class Media extends Model
{
    use Auditable;

    /** @var list<string> */
    protected array $auditExclude = ['variants', 'size'];

    protected function casts(): array
    {
        return ['variants' => 'array'];
    }

    protected static function booted(): void
    {
        // Al borrar el registro se borran también los archivos.
        static::deleted(function (Media $media): void {
            Storage::disk($media->original_disk)->delete($media->original_path);

            $firstVariant = collect($media->variants)->first();
            if ($firstVariant !== null) {
                Storage::disk($media->variants_disk)->deleteDirectory(dirname($firstVariant['path']));
            }
        });
    }

    /** Estados en los que la foto ya tiene versiones para mostrar. */
    public const VISIBLE_STATUSES = ['ready', 'reprocessing'];

    public function isVisible(): bool
    {
        return in_array($this->status, self::VISIBLE_STATUSES, true) && ! empty($this->variants);
    }

    public function isProcessing(): bool
    {
        return in_array($this->status, ['processing', 'reprocessing'], true);
    }

    public function mediable(): MorphTo
    {
        return $this->morphTo();
    }

    public function url(string $variant): ?string
    {
        $path = $this->variants[$variant]['path'] ?? null;

        return $path ? Storage::disk($this->variants_disk)->url($path) : null;
    }

    /** srcset con todas las versiones de la colección, de menor a mayor. */
    public function srcset(): string
    {
        return collect($this->variants ?? [])
            ->sortBy('width')
            ->map(fn (array $variant): string => Storage::disk($this->variants_disk)->url($variant['path']).' '.$variant['width'].'w')
            ->implode(', ');
    }
}
