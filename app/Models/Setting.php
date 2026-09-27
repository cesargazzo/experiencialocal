<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * Configuración de la plataforma que los administradores pueden cambiar sin
 * desplegar. Cada clave guarda un valor JSON y se cachea hasta que cambia.
 */
#[Fillable(['key', 'value', 'updated_by'])]
class Setting extends Model
{
    use Auditable;

    protected $primaryKey = 'key';

    protected $keyType = 'string';

    public $incrementing = false;

    protected function casts(): array
    {
        return ['value' => 'array'];
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function valueOf(string $key): ?array
    {
        return Cache::rememberForever(self::cacheKey($key), fn (): ?array => static::query()->find($key)?->value);
    }

    /**
     * @param  array<string, mixed>  $value
     */
    public static function store(string $key, array $value, ?User $by = null): self
    {
        $setting = static::query()->updateOrCreate(['key' => $key], ['value' => $value, 'updated_by' => $by?->getKey()]);
        Cache::forget(self::cacheKey($key));

        return $setting;
    }

    private static function cacheKey(string $key): string
    {
        return 'setting:'.$key;
    }
}
