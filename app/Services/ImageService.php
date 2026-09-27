<?php

namespace App\Services;

use App\Models\Media;
use GdImage;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;

/**
 * Guarda una imagen subida: el original en un disco privado y versiones
 * WebP recortadas a medida en el disco público. Las versiones se generan
 * con GD, que no copia metadatos: la ubicación GPS y los datos de la
 * cámara del original nunca quedan públicos.
 */
class ImageService
{
    /**
     * Reemplaza la imagen de una colección de un solo elemento (avatar, portada).
     */
    public function replace(Model $owner, string $collection, UploadedFile $file, ?string $alt = null): Media
    {
        $media = $this->store($owner, $collection, $file, $alt);

        $owner->morphMany(Media::class, 'mediable')
            ->where('collection', $collection)
            ->whereKeyNot($media->getKey())
            ->get()
            ->each->delete();

        return $media;
    }

    public function store(Model $owner, string $collection, UploadedFile $file, ?string $alt = null): Media
    {
        $settings = config("tinku.images.collections.{$collection}")
            ?? throw new InvalidArgumentException("Colección de imágenes desconocida: {$collection}");

        [$width, $height, $type] = @getimagesize($file->getRealPath()) ?: [0, 0, 0];
        if (! in_array($type, [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP], true)) {
            throw new InvalidArgumentException('La imagen tiene que ser JPG, PNG o WebP.');
        }
        if ($width * $height > config('tinku.images.max_megapixels') * 1_000_000) {
            throw new InvalidArgumentException('La imagen es demasiado grande.');
        }

        $uuid = (string) Str::uuid();
        $originalDisk = config('tinku.images.original_disk');
        $variantsDisk = config('tinku.images.variants_disk');
        $extension = image_type_to_extension($type, false) === 'jpeg' ? 'jpg' : image_type_to_extension($type, false);
        $originalPath = $file->storeAs("originales/{$collection}/".now()->format('Y/m'), "{$uuid}.{$extension}", $originalDisk);

        $source = $this->load($file->getRealPath(), $type);
        $variants = [];

        try {
            foreach ($settings['variants'] as $name => $variant) {
                [$targetWidth, $targetHeight] = $variant;
                $format = $variant[2] ?? 'webp';
                $path = "media/{$collection}/{$uuid}/{$name}.{$format}";
                $binary = $this->encodeCover($source, $targetWidth, $targetHeight, $format);
                Storage::disk($variantsDisk)->put($path, $binary, 'public');
                $variants[$name] = ['path' => $path, 'width' => $targetWidth, 'height' => $targetHeight, 'size' => strlen($binary)];
            }
        } finally {
            imagedestroy($source);
        }

        return $owner->morphMany(Media::class, 'mediable')->create([
            'uuid' => $uuid,
            'collection' => $collection,
            'original_disk' => $originalDisk,
            'original_path' => $originalPath,
            'original_name' => Str::limit($file->getClientOriginalName(), 250, ''),
            'mime_type' => image_type_to_mime_type($type),
            'size' => $file->getSize(),
            'width' => $width,
            'height' => $height,
            'variants_disk' => $variantsDisk,
            'variants' => $variants,
            'alt' => $alt,
        ]);
    }

    private function load(string $path, int $type): GdImage
    {
        $image = match ($type) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($path),
            IMAGETYPE_PNG => @imagecreatefrompng($path),
            IMAGETYPE_WEBP => @imagecreatefromwebp($path),
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
        imagealphablending($canvas, false);
        imagesavealpha($canvas, true);
        imagecopyresampled($canvas, $source, 0, 0, $cropX, $cropY, $targetWidth, $targetHeight, $cropWidth, $cropHeight);

        if ($format === 'jpg') {
            // JPEG no tiene transparencia: fondo blanco antes de copiar.
            imagealphablending($canvas, true);
            imagefill($canvas, 0, 0, imagecolorallocate($canvas, 255, 255, 255));
            imagecopyresampled($canvas, $source, 0, 0, $cropX, $cropY, $targetWidth, $targetHeight, $cropWidth, $cropHeight);
        }

        ob_start();
        $format === 'jpg'
            ? imagejpeg($canvas, null, 85)
            : imagewebp($canvas, null, (int) config('tinku.images.quality'));
        $binary = (string) ob_get_clean();
        imagedestroy($canvas);

        return $binary;
    }
}
