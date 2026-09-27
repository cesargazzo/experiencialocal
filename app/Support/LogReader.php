<?php

namespace App\Support;

use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Lee los archivos de storage/logs para que un administrador vea qué falló,
 * sin entrar al servidor. Lee solo el final de cada archivo.
 */
class LogReader
{
    /** Cuánto se lee desde el final del archivo. */
    public const TAIL_BYTES = 2 * 1024 * 1024;

    public function __construct(private readonly string $directory = '') {}

    public function directory(): string
    {
        return $this->directory ?: storage_path('logs');
    }

    /**
     * Archivos .log, el más reciente primero.
     *
     * @return Collection<int, array{name: string, size: int, modified: Carbon}>
     */
    public function files(): Collection
    {
        return collect(glob($this->directory().'/*.log') ?: [])
            ->map(fn (string $path) => ['name' => basename($path), 'size' => (int) filesize($path), 'modified' => Carbon::createFromTimestamp((int) filemtime($path))])
            ->sortByDesc('modified')
            ->values();
    }

    /**
     * Entradas del archivo, la más reciente primero.
     *
     * @return Collection<int, array{date: ?Carbon, environment: string, level: string, message: string, details: string}>
     */
    public function entries(string $file, ?string $level = null, ?string $search = null, int $limit = 200): Collection
    {
        $path = $this->pathFor($file);
        if ($path === null) {
            return collect();
        }

        $size = (int) filesize($path);
        $handle = fopen($path, 'rb');
        if ($handle === false) {
            return collect();
        }
        if ($size > self::TAIL_BYTES) {
            fseek($handle, -self::TAIL_BYTES, SEEK_END);
        }
        $content = (string) stream_get_contents($handle);
        fclose($handle);

        $parts = preg_split('/^(?=\[\d{4}-\d{2}-\d{2}[T ]\d{2}:\d{2}:\d{2})/m', $content) ?: [];

        return collect($parts)
            ->map(fn (string $chunk) => $this->parse($chunk))
            ->filter()
            ->when($level, fn (Collection $entries) => $entries->where('level', strtolower($level)))
            ->when($search, fn (Collection $entries) => $entries->filter(fn (array $entry) => Str::contains($entry['message'].$entry['details'], $search, ignoreCase: true)))
            ->reverse()
            ->take($limit)
            ->values();
    }

    /** Solo archivos .log de la carpeta, sin rutas. */
    public function pathFor(string $file): ?string
    {
        $name = basename($file);
        $path = $this->directory().'/'.$name;

        return str_ends_with($name, '.log') && is_file($path) ? $path : null;
    }

    /**
     * @return array{date: ?Carbon, environment: string, level: string, message: string, details: string}|null
     */
    private function parse(string $chunk): ?array
    {
        if (! preg_match('/^\[(?<date>[^\]]+)\]\s+(?<env>[\w-]+)\.(?<level>\w+):\s?(?<rest>.*)$/s', trim($chunk), $match)) {
            return null;
        }

        [$message, $details] = array_pad(explode("\n", $match['rest'], 2), 2, '');

        try {
            $date = Carbon::parse($match['date']);
        } catch (\Throwable) {
            $date = null;
        }

        return [
            'date' => $date,
            'environment' => $match['env'],
            'level' => strtolower($match['level']),
            'message' => Str::limit(trim($message), 2000),
            'details' => Str::limit(trim($details), 20000),
        ];
    }
}
