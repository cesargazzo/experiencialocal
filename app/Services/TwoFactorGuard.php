<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

/**
 * Único punto donde se comprueba un código de doble factor: limita los intentos
 * por persona (sin importar desde qué pantalla o IP llegan) y deja constancia.
 */
class TwoFactorGuard
{
    /** Intentos fallidos permitidos antes de bloquear. */
    public const MAX_ATTEMPTS = 5;

    /** Segundos que dura el bloqueo: con 5 intentos cada 15 minutos adivinar un código es impracticable. */
    public const LOCK_SECONDS = 900;

    /** Marca en la sesión de que en esta sesión se ingresó un código válido. */
    public const SESSION_KEY = 'auth.two_factor_at';

    public function __construct(private SecurityLog $securityLog) {}

    /**
     * Valida el código o corta con un error de validación en el campo "code".
     *
     * @return 'totp'|'recovery'
     *
     * @throws ValidationException
     */
    public function attempt(User $user, string $code, string $errorBag = 'default'): string
    {
        $key = '2fa:'.$user->getKey();

        if (RateLimiter::tooManyAttempts($key, self::MAX_ATTEMPTS)) {
            $minutes = (int) ceil(RateLimiter::availableIn($key) / 60);
            $this->securityLog->record('login.locked', $user, ['step' => '2fa', 'minutes_left' => $minutes], $user->email, 'danger');

            throw ValidationException::withMessages(['code' => __('Hubo demasiados intentos. Probá de nuevo en :time.', ['time' => plural_es($minutes, __('minuto'), __('minutos'))])])->errorBag($errorBag);
        }

        $method = $user->verifyTwoFactorCode($code);
        if (! $method) {
            RateLimiter::hit($key, self::LOCK_SECONDS);
            $this->securityLog->record('2fa.failed', $user, ['attempts' => RateLimiter::attempts($key)], $user->email, 'warning');

            throw ValidationException::withMessages(['code' => __('El código no coincide.')])->errorBag($errorBag);
        }

        RateLimiter::clear($key);
        if ($method === 'recovery') {
            $this->securityLog->record('2fa.recovery_used', $user, ['left' => count($user->two_factor_recovery_codes ?? [])], $user->email, 'warning');
        }

        return $method;
    }

    public function markPassed(Request $request): void
    {
        $request->session()->put(self::SESSION_KEY, now()->timestamp);
    }

    public function passed(Request $request): bool
    {
        return $request->session()->has(self::SESSION_KEY);
    }
}
