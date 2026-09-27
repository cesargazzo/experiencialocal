<?php

namespace App\Support;

/**
 * Países para residencia, nacionalidad y documentos. Argentina primero, después
 * los países de la región y el resto del mundo, cada grupo en orden alfabético.
 * "XX" es "Otro país" para quien no está en la lista.
 */
class CountryList
{
    /** @var array<string, string> Latinoamérica y el Caribe. */
    private const REGION = [
        'BO' => 'Bolivia', 'BR' => 'Brasil', 'CL' => 'Chile', 'CO' => 'Colombia', 'CR' => 'Costa Rica', 'CU' => 'Cuba',
        'EC' => 'Ecuador', 'SV' => 'El Salvador', 'GT' => 'Guatemala', 'GY' => 'Guyana', 'HN' => 'Honduras',
        'MX' => 'México', 'NI' => 'Nicaragua', 'PA' => 'Panamá', 'PY' => 'Paraguay', 'PE' => 'Perú',
        'PR' => 'Puerto Rico', 'DO' => 'República Dominicana', 'SR' => 'Surinam', 'UY' => 'Uruguay', 'VE' => 'Venezuela',
    ];

    /** @var array<string, string> */
    private const WORLD = [
        'DE' => 'Alemania', 'AU' => 'Australia', 'AT' => 'Austria', 'BE' => 'Bélgica', 'CA' => 'Canadá', 'CN' => 'China',
        'KR' => 'Corea del Sur', 'DK' => 'Dinamarca', 'ES' => 'España', 'US' => 'Estados Unidos', 'FR' => 'Francia',
        'IN' => 'India', 'IE' => 'Irlanda', 'IL' => 'Israel', 'IT' => 'Italia', 'JP' => 'Japón', 'NO' => 'Noruega',
        'NZ' => 'Nueva Zelanda', 'NL' => 'Países Bajos', 'PL' => 'Polonia', 'PT' => 'Portugal', 'GB' => 'Reino Unido',
        'SE' => 'Suecia', 'CH' => 'Suiza',
    ];

    /**
     * @return array<string, string>
     */
    public static function all(): array
    {
        return array_merge(...array_values(self::grouped()));
    }

    /**
     * Para listas con <optgroup>.
     *
     * @return array<string, array<string, string>>
     */
    public static function grouped(): array
    {
        return [
            'Argentina' => ['AR' => 'Argentina'],
            'Países de la región' => self::sorted(self::REGION),
            'Resto del mundo' => self::sorted(self::WORLD) + ['XX' => 'Otro país'],
        ];
    }

    /**
     * @return list<string>
     */
    public static function codes(): array
    {
        return array_keys(self::all());
    }

    /**
     * @param  array<string, string>  $countries
     * @return array<string, string>
     */
    private static function sorted(array $countries): array
    {
        uasort($countries, fn (string $a, string $b): int => strcmp(self::sortKey($a), self::sortKey($b)));

        return $countries;
    }

    private static function sortKey(string $name): string
    {
        return strtr(mb_strtolower($name), ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u']);
    }
}
