<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * La administración ve datos personales: entrar exige tener el doble factor activado.
 */
class EnsureAdminHasTwoFactor
{
    public function handle(Request $request, Closure $next): Response
    {
        if (config('tinku.admin_requires_two_factor') && ! $request->user()?->hasTwoFactor()) {
            return redirect()->to(route('cuenta.seguridad').'#doble-factor')
                ->with('status', 'Para entrar a la administración activá el doble factor.');
        }

        return $next($request);
    }
}
