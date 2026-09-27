<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Con la indexación apagada (TINKU_INDEXABLE=false), toda respuesta le pide a
 * los buscadores que no la indexen, además del robots.txt y la meta etiqueta.
 */
class BlockIndexingWhenDisabled
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! config('tinku.indexable')) {
            $response->headers->set('X-Robots-Tag', 'noindex, nofollow');
        }

        return $response;
    }
}
