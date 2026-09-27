<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Zona horaria de referencia
    |--------------------------------------------------------------------------
    |
    | La base guarda todo en UTC. Cada experiencia se muestra en la hora de
    | su provincia; esta zona se usa cuando no hay un lugar asociado.
    |
    */

    'timezone' => env('TINKU_TIMEZONE', 'America/Argentina/Buenos_Aires'),

    /*
    |--------------------------------------------------------------------------
    | Política de contraseñas
    |--------------------------------------------------------------------------
    |
    | Valores por defecto. Un administrador puede cambiarlos desde el panel
    | (/admin/contrasenas) y lo que guarde ahí tiene prioridad. La longitud
    | mínima nunca baja de 8 caracteres.
    |
    */

    'password' => [
        'min_length' => (int) env('TINKU_PASSWORD_MIN_LENGTH', 10),
        'require_mixed_case' => (bool) env('TINKU_PASSWORD_MIXED_CASE', true),
        'require_numbers' => (bool) env('TINKU_PASSWORD_NUMBERS', true),
        'require_symbols' => (bool) env('TINKU_PASSWORD_SYMBOLS', false),
        'check_uncompromised' => (bool) env('TINKU_PASSWORD_UNCOMPROMISED', false),
        'max_login_attempts' => (int) env('TINKU_LOGIN_MAX_ATTEMPTS', 5),
        'lockout_minutes' => (int) env('TINKU_LOGIN_LOCKOUT_MINUTES', 15),
    ],

];
