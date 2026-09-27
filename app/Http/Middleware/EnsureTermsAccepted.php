<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Si hay una versión nueva de los términos que exige aceptación, la cuenta
 * tiene que aceptarla antes de seguir. Puede leerlos, cambiar su contraseña o salir.
 */
class EnsureTermsAccepted
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (
            $user
            && ! $request->routeIs('terminos', 'terminos.version', 'terminos.aceptar', 'terminos.aceptar.store', 'logout', 'cuenta.seguridad', 'cuenta.seguridad.update')
            && ! $request->hasHeader('X-Livewire')
            && ! $user->hasAcceptedRequiredTerms()
        ) {
            if ($request->isMethod('GET')) {
                $request->session()->put('url.intended', $request->fullUrl());
            }

            return redirect()->route('terminos.aceptar');
        }

        return $next($request);
    }
}
