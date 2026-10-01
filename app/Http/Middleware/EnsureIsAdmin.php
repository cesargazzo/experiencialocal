<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Solo el equipo entra a la administración; cada sección pide además su permiso.
 */
class EnsureIsAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless($request->user()?->isTeamMember(), 403);

        return $next($request);
    }
}
