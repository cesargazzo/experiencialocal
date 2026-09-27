<?php

namespace App\Models\Concerns;

use App\Models\AuditLog;
use BackedEnum;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;

/**
 * Registra en audit_logs cada alta, cambio y baja del modelo.
 *
 * Los campos de $auditExclude no se guardan. Los de $auditMasked se anotan
 * como "cambió" sin su valor (datos cifrados o sensibles).
 */
trait Auditable
{
    /** Nunca se guardan, en ningún modelo. */
    private static array $alwaysExcluded = ['password', 'remember_token', 'created_at', 'updated_at', 'token_hash'];

    public static function bootAuditable(): void
    {
        static::created(fn (Model $model) => AuditLog::record($model, 'created', null, $model->auditValues($model->getAttributes())));

        static::updated(function (Model $model) {
            $changes = $model->auditValues($model->getChanges());
            if ($changes === []) {
                return;
            }

            $old = [];
            foreach (array_keys($changes) as $key) {
                $old[$key] = $model->auditValue($key, $model->getOriginal($key));
            }

            AuditLog::record($model, 'updated', $old, $changes);
        });

        static::deleted(fn (Model $model) => AuditLog::record($model, 'deleted', $model->auditValues($model->getAttributes()), null));
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    public function auditValues(array $attributes): array
    {
        $excluded = [...self::$alwaysExcluded, ...($this->auditExclude ?? [])];
        $values = [];

        foreach ($attributes as $key => $raw) {
            if (in_array($key, $excluded, true)) {
                continue;
            }
            $values[$key] = $this->auditValue($key, $this->getAttribute($key));
        }

        return $values;
    }

    public function auditValue(string $key, mixed $value): mixed
    {
        if (in_array($key, $this->auditMasked ?? [], true)) {
            return $value === null ? null : '*** (dato protegido)';
        }

        return match (true) {
            $value instanceof BackedEnum => $value->value,
            $value instanceof DateTimeInterface => $value->format('Y-m-d H:i:s'),
            is_object($value) && method_exists($value, 'toArray') => $value->toArray(),
            default => $value,
        };
    }
}
