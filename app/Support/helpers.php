<?php

if (! function_exists('money')) {
    /** Formato de moneda argentino: "$ 40.000". */
    function money(float|int|string $amount, string $currency = 'ARS'): string
    {
        $formatted = number_format((float) $amount, 0, ',', '.');

        return match ($currency) {
            'USD' => 'US$ '.$formatted,
            default => '$ '.$formatted,
        };
    }
}

if (! function_exists('plural_es')) {
    /** "1 opinión", "3 opiniones". */
    function plural_es(int $count, string $singular, string $plural): string
    {
        return $count.' '.($count === 1 ? $singular : $plural);
    }
}
