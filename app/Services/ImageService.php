<?php

namespace App\Services;

use App\Jobs\ProcessMediaVariants;
use App\Models\Media;
use GdImage;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;

/**
 * Fotos subidas: el original se guarda al instante en un disco privado y las
 * versiones WebP (o JPEG para compartir en redes) se generan en segundo
 * plano. GD no copia metadatos: la ubicación GPS y los datos de la cámara
 * del original nunca quedan públicos.
 */
class ImageService
{
    /**
     * Sube una foto nueva para una colección de un solo elemento (avatar,
     * portada). La foto anterior sigue visible hasta que la nueva está lista.
     */
    public function replace(Model $owner, string $collection, UploadedFile $file, ?string $alt = null): Media
    {
        return $this->store($owner, $collection, $file, $alt);
    }

    public function store(Model $owner, string $collection, UploadedFile $file, ?string $alt = null): Media
    {
        $this->settings($collection);

        [$width, $height, $type] = @getimagesize($file->getRealPath()) ?: [0, 0, 0];
        if (! in_array($type, [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP], true)) {
            throw new InvalidArgumentException('La imagen tiene que ser JPG, PNG o WebP.');
        }
        if ($width * $height > config('tinku.images.max_megapixels') * 1_000_000) {
            throw new InvalidArgumentException('La imagen es demasiado grande.');
        }

        $uuid = (string) Str::uuid();
        $originalDisk = config('tinku.images.original_disk');
        $extension = $type === IMAGETYPE_JPEG ? 'jpg' : image_type_to_extension($type, false);
        $originalPath = $file->storeAs("originales/{$collection}/".now()->format('Y/m'), "{$uuid}.{$extension}", $originalDisk);

        $media = $owner->morphMany(Media::class, 'mediable')->create([
            'uuid' => $uuid,
            'collection' => $collection,
            'status' => 'processing',
            'rotation' => 0,
            'original_disk' => $originalDisk,
            'original_path' => $originalPath,
            'original_name' => Str::limit($file->getClientOriginalName(), 250, ''),
            'mime_type' => image_type_to_mime_type($type),
            'size' => $file->getSize(),
            'width' => $width,
            'height' => $height,
            'variants_disk' => config('tinku.images.variants_disk'),
            'variants' => null,
            'alt' => $alt,
        ]);

        ProcessMediaVariants::dispatch($media);

        return $media;
    }

    /** Gira 90° a la derecha. Las versiones se regeneran desde el original. */
    public function rotate(Media $media): Media
    {
        $media->forceFill([
            'rotation' => ($media->rotation + 90) % 360,
            'status' => $media->variants ? 'reprocessing' : 'processing',
            'error' => null,
        ])->save();

        ProcessMediaVariants::dispatch($media);

        return $media;
    }

    /** Genera las versiones. Lo llama el trabajo en cola. */
    public function generateVariants(Media $media): void
    {
        $media->refresh();
        $settings = $this->settings($media->collection);
        $previous = $media->variants ?? [];

        [$localPath, $isTemporary] = $this->localCopyOfOriginal($media);

        try {
            [, , $type] = getimagesize($localPath) ?: [0, 0, 0];
            $source = $this->load($localPath, $type);

            if ($media->rotation) {
                $rotated = imagerotate($source, -$media->rotation, 0);
                if (! $rotated instanceof GdImage) {
                    throw new RuntimeException('No pudimos girar la imagen.');
                }
                imagedestroy($source);
                $source = $rotated;
            }

            $variants = [];
            try {
                foreach ($settings['variants'] as $name => $variant) {
                    [$targetWidth, $targetHeight] = $variant;
                    $format = $variant[2] ?? 'webp';
                    // La rotación va en el nombre: al girar cambia la URL y el navegador no muestra la vieja.
                    $path = "media/{$media->collection}/{$media->uuid}/{$name}-r{$media->rotation}.{$format}";
                    $binary = $this->encodeCover($source, $targetWidth, $targetHeight, $format);
                    Storage::disk($media->variants_disk)->put($path, $binary, 'public');
                    $variants[$name] = ['path' => $path, 'width' => $targetWidth, 'height' => $targetHeight, 'size' => strlen($binary)];
                }
            } finally {
                imagedestroy($source);
            }
        } finally {
            if ($isTemporary) {
                @unlink($localPath);
            }
        }

        $media->forceFill(['variants' => $variants, 'status' => 'ready', 'error' => null])->save();

        // Borra las versiones anteriores que ya no se usan.
        $stale = collect($previous)->pluck('path')->diff(collect($variants)->pluck('path'))->all();
        if ($stale) {
            Storage::disk($media->variants_disk)->delete($stale);
        }

        // Recién ahora que la nueva está lista, se borra la foto anterior de la colección.
        if ($media->mediable_type && ($settings['single'] ?? true)) {
            Media::query()
                ->where('mediable_type', $media->mediable_type)
                ->where('mediable_id', $media->mediable_id)
                ->where('collection', $media->collection)
                ->whereKeyNot($media->getKey())
                ->where('id', '<', $media->getKey())
                ->get()
                ->each->delete();
        }
    }

    /**
     * @return array{min: array{0: int, 1: int}, variants: array<string, array{0: int, 1: int, 2?: string}>, single?: bool}
     */
    private function settings(string $collection): array
    {
        return config("tinku.images.collections.{$collection}")
            ?? throw new InvalidArgumentException("Colección de imágenes desconocida: {$collection}");
    }

    /**
     * @return array{0: string, 1: bool} Ruta local y si hay que borrarla al terminar.
     */
    private function localCopyOfOriginal(Media $media): array
    {
        $disk = Storage::disk($media->original_disk);

        if (! $disk->exists($media->original_path)) {
            throw new RuntimeException('No encontramos el original de la foto.');
        }

        try {
            return [$disk->path($media->original_path), false];
        } catch (RuntimeException) {
            // Discos remotos (por ejemplo S3): se baja a un archivo temporal.
            $temporary = tempnam(sys_get_temp_dir(), 'tinku-img-');
            file_put_contents($temporary, $disk->get($media->original_path));

            return [$temporary, true];
        }
    }

    private function load(string $path, int $type): GdImage
    {
        $image = match ($type) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($path),
            IMAGETYPE_PNG => @imagecreatefrompng($path),
            IMAGETYPE_WEBP => @imagecreatefromwebp($path),
            default => false,
        };

        if (! $image instanceof GdImage) {
            throw new RuntimeException('No pudimos leer la imagen.');
        }

        return $type === IMAGETYPE_JPEG ? $this->applyExifOrientation($image, $path) : $image;
    }

    /** Las fotos de celular guardan la rotación en EXIF; se aplica antes de descartar los metadatos. */
    private function applyExifOrientation(GdImage $image, string $path): GdImage
    {
        $orientation = function_exists('exif_read_data') ? (@exif_read_data($path)['Orientation'] ?? 1) : 1;

        $rotated = match ((int) $orientation) {
            3 => imagerotate($image, 180, 0),
            6 => imagerotate($image, -90, 0),
            8 => imagerotate($image, 90, 0),
            default => null,
        };

        if ($rotated instanceof GdImage) {
            imagedestroy($image);

            return $rotated;
        }

        return $image;
    }

    /** Recorta al centro para cubrir exactamente el tamaño pedido y lo codifica en WebP o JPEG. */
    private function encodeCover(GdImage $source, int $targetWidth, int $targetHeight, string $format = 'webp'): string
    {
        $sourceWidth = imagesx($source);
        $sourceHeight = imagesy($source);
        $scale = max($targetWidth / $sourceWidth, $targetHeight / $sourceHeight);
        $cropWidth = (int) round($targetWidth / $scale);
        $cropHeight = (int) round($targetHeight / $scale);
        $cropX = (int) floor(($sourceWidth - $cropWidth) / 2);
        $cropY = (int) floor(($sourceHeight - $cropHeight) / 2);

        $canvas = imagecreatetruecolor($targetWidth, $targetHeight);

        if ($format === 'jpg') {
            // JPEG no tiene transparencia: fondo blanco antes de copiar.
            imagefill($canvas, 0, 0, imagecolorallocate($canvas, 255, 255, 255));
        } else {
            imagealphablending($canvas, false);
            imagesavealpha($canvas, true);
        }
        imagecopyresampled($canvas, $source, 0, 0, $cropX, $cropY, $targetWidth, $targetHeight, $cropWidth, $cropHeight);

        ob_start();
        $format === 'jpg'
            ? imagejpeg($canvas, null, 85)
            : imagewebp($canvas, null, (int) config('tinku.images.quality'));
        $binary = (string) ob_get_clean();
        imagedestroy($canvas);

        return $binary;
    }
}
