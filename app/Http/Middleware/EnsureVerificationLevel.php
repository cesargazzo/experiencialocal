<?php

namespace App\Http\Middleware;

use App\Enums\VerificationLevel;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Uso: ->middleware('verified.level:2')
 */
class EnsureVerificationLevel
{
    public function handle(Request $request, Closure $next, int $level): Response
    {
        $required = VerificationLevel::from($level);
        $user = $request->user();

        if (! $user || ! $user->hasVerificationLevel($required)) {
            return redirect()
                ->route('verificacion')
                ->with('status', sprintf('Para continuar necesitás el nivel de verificación "%s".', $required->label()));
        }

        return $next($request);
    }
}
