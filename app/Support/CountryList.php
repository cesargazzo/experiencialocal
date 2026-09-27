<?php

namespace App\Support;

/**
 * Países para nacionalidad y documentos. Argentina primero, el resto en
 * orden alfabético. "XX" es "Otro país" para quien no está en la lista.
 */
class CountryList
{
    /** @var array<string, string> */
    private const COUNTRIES = [
        'DE' => 'Alemania', 'BO' => 'Bolivia', 'BR' => 'Brasil', 'CA' => 'Canadá', 'CL' => 'Chile', 'CN' => 'China',
        'CO' => 'Colombia', 'KR' => 'Corea del Sur', 'CR' => 'Costa Rica', 'CU' => 'Cuba', 'EC' => 'Ecuador',
        'ES' => 'España', 'US' => 'Estados Unidos', 'FR' => 'Francia', 'GB' => 'Reino Unido', 'IL' => 'Israel',
        'IT' => 'Italia', 'JP' => 'Japón', 'MX' => 'México', 'NL' => 'Países Bajos', 'PY' => 'Paraguay',
        'PE' => 'Perú', 'PT' => 'Portugal', 'CH' => 'Suiza', 'UY' => 'Uruguay', 'VE' => 'Venezuela',
    ];

    /**
     * @return array<string, string>
     */
    public static function all(): array
    {
        $countries = self::COUNTRIES;
        asort($countries, SORT_LOCALE_STRING);

        return ['AR' => 'Argentina'] + $countries + ['XX' => 'Otro país'];
    }

    /**
     * @return list<string>
     */
    public static function codes(): array
    {
        return array_keys(self::all());
    }
}
