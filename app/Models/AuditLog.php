<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Throwable;

/**
 * Auditoría: quién cambió qué, cuándo y desde dónde, con valores anteriores
 * y nuevos. Se completa sola con el trait Auditable.
 */
#[Fillable(['auditable_type', 'auditable_id', 'event', 'user_id', 'old_values', 'new_values', 'source', 'ip', 'user_agent', 'path', 'created_at'])]
class AuditLog extends Model
{
    use MassPrunable;

    public const UPDATED_AT = null;

    /** Nombres para mostrar de cada tabla auditada. @var array<class-string, string> */
    public const MODEL_LABELS = [
        User::class => 'Usuario',
        HostProfile::class => 'Perfil de anfitrión',
        Experience::class => 'Experiencia',
        ExperienceDate::class => 'Fecha',
        Booking::class => 'Reserva',
        Review::class => 'Opinión',
        IdentityVerification::class => 'Verificación',
        Plan::class => 'Plan',
        Category::class => 'Categoría',
        Setting::class => 'Configuración',
        Invitation::class => 'Invitación',
        Media::class => 'Foto',
        Subscription::class => 'Suscripción',
        Country::class => 'País',
        Province::class => 'Provincia',
    ];

    /** @var array<string, string> */
    public const EVENT_LABELS = [
        'created' => 'Alta',
        'updated' => 'Cambio',
        'deleted' => 'Baja',
        'interests.updated' => 'Intereses',
    ];

    protected function casts(): array
    {
        return [
            'old_values' => 'array',
            'new_values' => 'array',
            'created_at' => 'datetime',
        ];
    }

    /**
     * @param  array<string, mixed>|null  $old
     * @param  array<string, mixed>|null  $new
     */
    public static function record(Model $model, string $event, ?array $old, ?array $new): void
    {
        if (! self::tableReady()) {
            return;
        }

        try {
            $inHttp = ! app()->runningInConsole() || app()->runningUnitTests();
            $request = $inHttp ? request() : null;

            // Dentro de un punto de guardado: si esta escritura falla, no invalida
            // la transacción de la operación que se está auditando (PostgreSQL).
            DB::transaction(fn () => static::create([
                'auditable_type' => $model::class,
                'auditable_id' => (string) $model->getKey(),
                'event' => $event,
                'user_id' => auth()->id(),
                'old_values' => $old ?: null,
                'new_values' => $new ?: null,
                'source' => app()->runningInConsole() && ! app()->runningUnitTests() ? 'consola' : 'web',
                'ip' => $request?->ip(),
                'user_agent' => $request ? Str::limit((string) $request->userAgent(), 500, '') : null,
                'path' => $request ? Str::limit($request->path(), 500, '') : null,
            ]));
        } catch (Throwable $e) {
            // La auditoría nunca frena la operación; si falla, queda en el log de archivos.
            Log::warning('No se pudo guardar la auditoría', ['model' => $model::class, 'event' => $event, 'error' => $e->getMessage()]);
        }
    }

    /** Durante las migraciones la tabla puede no existir todavía. Solo se recuerda cuando ya existe. */
    private static bool $tableReady = false;

    private static function tableReady(): bool
    {
        return self::$tableReady = self::$tableReady || Schema::hasTable('audit_logs');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function modelLabel(): string
    {
        return self::MODEL_LABELS[$this->auditable_type] ?? class_basename($this->auditable_type);
    }

    public function eventLabel(): string
    {
        return self::EVENT_LABELS[$this->event] ?? $this->event;
    }

    /**
     * Campos que cambiaron, con valor anterior y nuevo.
     *
     * @return array<string, array{0: mixed, 1: mixed}>
     */
    public function changes(): array
    {
        $keys = array_unique([...array_keys($this->old_values ?? []), ...array_keys($this->new_values ?? [])]);

        return collect($keys)->mapWithKeys(fn (string $key) => [$key => [$this->old_values[$key] ?? null, $this->new_values[$key] ?? null]])->all();
    }

    public function prunable(): Builder
    {
        return static::query()->where('created_at', '<', now()->subDays((int) config('tinku.audit_log_days')));
    }
}
