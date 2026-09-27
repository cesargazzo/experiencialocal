<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

/**
 * Última actividad de la cuenta, para ver quién usa Tinku aunque su sesión
 * dure días. Se escribe como mucho una vez cada cinco minutos.
 */
class RecordLastSeen
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && ($user->last_seen_at === null || $user->last_seen_at->lt(now()->subMinutes(5)))) {
            DB::table('users')->where('id', $user->id)->update(['last_seen_at' => now()]);
        }

        return $next($request);
    }
}
