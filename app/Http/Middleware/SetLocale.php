<?php

namespace App\Http\Middleware;

use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Idioma de la interfaz: el que eligió la persona (sesión o cuenta) o, la primera
 * vez, el de su navegador. Si no es ninguno de los disponibles, castellano.
 */
class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $available = array_keys(config('tinku.locales'));
        $chosen = $request->session()->get('locale') ?? $request->user()?->locale;

        if (! in_array($chosen, $available, true)) {
            $chosen = self::fromBrowser($request, $available);
        }

        app()->setLocale($chosen);
        Carbon::setLocale($chosen);

        return $next($request);
    }

    /**
     * @param  list<string>  $available
     */
    public static function fromBrowser(Request $request, array $available): string
    {
        foreach ($request->getLanguages() as $language) {
            // "pt-BR", "pt" y "pt_PT" van al portugués de Brasil; "en-US" al inglés.
            $prefix = strtolower(substr($language, 0, 2));
            $match = collect($available)->first(fn (string $locale) => strtolower(substr($locale, 0, 2)) === $prefix);
            if ($match) {
                return $match;
            }
        }

        return $available[0];
    }
}
