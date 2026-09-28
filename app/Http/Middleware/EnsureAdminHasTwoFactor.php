<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Services\TwoFactorGuard;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * La administración ve datos personales: entrar exige tener el doble factor activado
 * y haber ingresado un código en esta misma sesión (salvo que esté apagado para toda la plataforma).
 */
class EnsureAdminHasTwoFactor
{
    public function __construct(private TwoFactorGuard $guard) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (! User::twoFactorAvailable() || ! config('tinku.admin_requires_two_factor')) {
            return $next($request);
        }

        if (! $request->user()?->hasTwoFactor()) {
            return redirect()->to(route('cuenta.seguridad').'#doble-factor')
                ->with('status', 'Para entrar a la administración activá el doble factor.');
        }

        if (! $this->guard->passed($request)) {
            return redirect()->guest(route('cuenta.2fa.verify'));
        }

        return $next($request);
    }
}
