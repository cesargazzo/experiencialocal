<?php

namespace App\Services;

use App\Models\SecurityEvent;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

/**
 * Anota eventos de seguridad. Nunca interrumpe el pedido: si no puede
 * escribir en la base, lo deja en el log de archivos.
 */
class SecurityLog
{
    /** Rutas que suelen probar los escáneres automáticos. */
    private const PROBE_PATTERN = '#(^|/)(wp-|wordpress|xmlrpc\.php|phpmyadmin|pma|\.env|\.git|\.aws|\.ssh|config\.php|admin\.php|cgi-bin|vendor/phpunit|server-status|actuator|boaform|\.well-known/security\.txt\.php)#i';

    /**
     * @param  array<string, mixed>  $metadata
     */
    public function record(string $type, ?User $user = null, array $metadata = [], ?string $email = null, string $severity = 'info'): void
    {
        try {
            $request = request();

            SecurityEvent::create([
                'type' => $type,
                'severity' => $severity,
                'user_id' => $user?->getKey() ?? $request->user()?->getKey(),
                'email' => $email !== null ? Str::lower(Str::limit(trim($email), 250, '')) : null,
                'ip' => $request->ip(),
                'user_agent' => Str::limit((string) $request->userAgent(), 500, ''),
                'method' => $request->method(),
                'path' => Str::limit($this->safePath($request->path()), 500, ''),
                'metadata' => $metadata ?: null,
            ]);
        } catch (Throwable $e) {
            Log::warning('No se pudo guardar el evento de seguridad', ['type' => $type, 'error' => $e->getMessage()]);
        }
    }

    public function isProbe(string $path): bool
    {
        return (bool) preg_match(self::PROBE_PATTERN, $path);
    }

    /** Nunca guarda tokens que viajan en la URL, como el de recuperar la contraseña. */
    private function safePath(string $path): string
    {
        return (string) preg_replace('#^(restablecer-contrasena)/[^/]+#', '$1/***', $path);
    }
}
