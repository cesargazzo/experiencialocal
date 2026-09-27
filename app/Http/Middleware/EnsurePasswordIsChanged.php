<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Con una contraseña de única vez, la cuenta solo puede cambiarla o salir.
 */
class EnsurePasswordIsChanged
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user?->must_change_password && ! $request->routeIs('cuenta.contrasena', 'cuenta.contrasena.update', 'logout')) {
            return redirect()->route('cuenta.contrasena')->with('status', 'Elegí una contraseña nueva para seguir.');
        }

        return $next($request);
    }
}
