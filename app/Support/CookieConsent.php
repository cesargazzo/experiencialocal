<?php

namespace App\Support;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Cookie;

/**
 * La elección de cada visitante sobre las cookies opcionales. Las necesarias
 * (sesión, seguridad y esta misma elección) no dependen de esto; las de
 * análisis solo se cargan si la persona las aceptó.
 */
class CookieConsent
{
    public const COOKIE = 'tinku-cookies';

    /** Si cambia lo que se pide, subir la versión vuelve a preguntar a todos. */
    public const VERSION = 1;

    public const DAYS = 365;

    public function __construct(public readonly bool $analytics) {}

    /** La elección guardada, o null si todavía no eligió (o eligió sobre una versión anterior). */
    public static function fromRequest(Request $request): ?self
    {
        $value = (string) $request->cookie(self::COOKIE);
        if (! preg_match('/^(\d+):([01])$/', $value, $matches) || (int) $matches[1] !== self::VERSION) {
            return null;
        }

        return new self($matches[2] === '1');
    }

    /** Hay que preguntar solo si hay algo opcional para aceptar. */
    public static function hasOptionalCookies(): bool
    {
        return filled(config('services.google_analytics.id'));
    }

    public function toCookie(): Cookie
    {
        return cookie(self::COOKIE, self::VERSION.':'.($this->analytics ? '1' : '0'), self::DAYS * 24 * 60);
    }
}
