<?php

namespace App\Http\Middleware;

use App\Services\SecurityLog;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

/**
 * Corta a las IP que rastrean el sitio buscando agujeros (wp-login, .env, phpMyAdmin…):
 * después de varios intentos en pocos minutos quedan bloqueadas una hora para todo el sitio.
 * Para levantar un bloqueo a mano: php artisan cache:forget probe-ban:{ip}
 */
class BlockProbingClients
{
    /** Rastreos permitidos en la ventana antes de bloquear. */
    public const THRESHOLD = 5;

    public const WINDOW_SECONDS = 600;

    public const BAN_SECONDS = 3600;

    public function handle(Request $request, Closure $next): Response
    {
        if (Cache::has(self::banKey((string) $request->ip()))) {
            return response('', 403);
        }

        return $next($request);
    }

    /** Lo llama el manejo de errores cuando un 404 tiene pinta de rastreo. */
    public static function recordProbe(Request $request, SecurityLog $securityLog): void
    {
        $ip = (string) $request->ip();
        $securityLog->record('probe.suspicious', null, [], null, 'danger');

        RateLimiter::hit('probe:'.$ip, self::WINDOW_SECONDS);
        if (RateLimiter::attempts('probe:'.$ip) >= self::THRESHOLD && ! Cache::has(self::banKey($ip))) {
            Cache::put(self::banKey($ip), true, self::BAN_SECONDS);
            $securityLog->record('probe.banned', null, ['minutes' => self::BAN_SECONDS / 60], null, 'danger');
        }
    }

    public static function banKey(string $ip): string
    {
        return 'probe-ban:'.$ip;
    }
}
