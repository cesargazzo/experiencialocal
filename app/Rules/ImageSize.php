<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\UploadedFile;

/**
 * Valida una foto con mensajes claros: formato, peso y lado más corto.
 * Acepta cualquier orientación (horizontal o vertical).
 */
class ImageSize implements ValidationRule
{
    public function __construct(private readonly int $minSide, private readonly int $maxKilobytes) {}

    public static function forCollection(string $collection): self
    {
        return new self(
            (int) config("tinku.images.collections.{$collection}.min_side"),
            (int) config('tinku.images.max_upload_kb'),
        );
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! $value instanceof UploadedFile || ! $value->isValid()) {
            $fail('No pudimos recibir la foto. Probá de nuevo.');

            return;
        }

        if ($value->getSize() > $this->maxKilobytes * 1024) {
            $fail('La foto pesa '.round($value->getSize() / 1048576, 1).' MB y el máximo es '.round($this->maxKilobytes / 1024).' MB.');

            return;
        }

        $info = @getimagesize($value->getRealPath());
        if (! $info || ! in_array($info[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP], true)) {
            $fail('La foto tiene que ser JPG, PNG o WebP. Si es del iPhone (HEIC), exportala como JPG.');

            return;
        }

        [$width, $height] = $info;
        if (min($width, $height) < $this->minSide) {
            $fail("La foto mide {$width} × {$height} px y tiene que tener al menos {$this->minSide} px de cada lado. Probá con la original del celular, sin recortar.");
        }
    }
}
