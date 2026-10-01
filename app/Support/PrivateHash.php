<?php

namespace App\Support;

/**
 * Huellas de datos personales con clave secreta (HMAC). Sirven para encontrar
 * duplicados o buscar por número exacto sin guardar el número. Sin la clave no
 * se pueden recalcular: aunque se filtre la base, no se puede probar uno por
 * uno todos los DNI o teléfonos posibles hasta dar con el que coincide.
 *
 * La clave sale de APP_KEY: si algún día se rota, hay que recalcular las huellas.
 */
class PrivateHash
{
    public static function of(string $value): string
    {
        return hash_hmac('sha256', $value, 'tinku-private-hash|'.config('app.key'));
    }

    /** Teléfono: solo dígitos y los últimos 10 (código de área y número), así coincide con o sin +54, 9, 0 o 15. */
    public static function phone(string $phone): ?string
    {
        $digits = preg_replace('/\D/', '', $phone) ?? '';
        if (strlen($digits) < 8) {
            return null;
        }

        return self::of('phone|'.substr($digits, -10));
    }

    /** "+54 380 412-3456" → "•••• 3456": para mostrar a dónde se mandó un código sin guardar el número. */
    public static function maskPhone(?string $phone): ?string
    {
        $digits = preg_replace('/\D/', '', (string) $phone) ?? '';

        return $digits === '' ? null : '•••• '.substr($digits, -4);
    }
}
