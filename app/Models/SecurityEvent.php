<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Registro de seguridad: accesos, cambios de contraseña, acciones de
 * administración, accesos denegados y errores. Se borra solo después de
 * config('tinku.security_log_days') días.
 */
#[Fillable(['type', 'severity', 'user_id', 'email', 'ip', 'user_agent', 'method', 'path', 'metadata', 'created_at'])]
class SecurityEvent extends Model
{
    use MassPrunable;

    public const UPDATED_AT = null;

    /** @var array<string, string> */
    public const LABELS = [
        'login.succeeded' => 'Ingreso',
        'login.failed' => 'Ingreso fallido',
        'login.locked' => 'Bloqueo por intentos',
        'logout' => 'Salida',
        'register' => 'Cuenta nueva',
        'password.changed' => 'Cambio de contraseña',
        'password.reset_requested' => 'Pedido de recuperación',
        'password.reset' => 'Contraseña recuperada',
        'password.reset_failed' => 'Recuperación rechazada',
        'password.temporary_issued' => 'Contraseña de única vez',
        'admin.granted' => 'Permiso de administración',
        'admin.revoked' => 'Permiso de administración quitado',
        'policy.updated' => 'Política de contraseñas',
        'verification.approved' => 'Verificación aprobada',
        'verification.rejected' => 'Verificación rechazada',
        'access.forbidden' => 'Acceso denegado',
        'request.throttled' => 'Demasiados pedidos',
        'session.expired' => 'Sesión o formulario vencido',
        'probe.suspicious' => 'Rastreo sospechoso',
        'error.server' => 'Error del servidor',
        'invitation.sent' => 'Invitación por email',
        'invitation.link_created' => 'Enlace de invitación',
        'invitation.accepted' => 'Invitación aceptada',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function label(): string
    {
        return self::LABELS[$this->type] ?? $this->type;
    }

    public function prunable(): Builder
    {
        return static::query()->where('created_at', '<', now()->subDays((int) config('tinku.security_log_days')));
    }
}
