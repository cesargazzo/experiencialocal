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
    | Edad mínima para crear una cuenta
    |--------------------------------------------------------------------------
    */

    'min_age' => (int) env('TINKU_MIN_AGE', 18),

    /*
    |--------------------------------------------------------------------------
    | Indexación en buscadores
    |--------------------------------------------------------------------------
    |
    | Solo producción se indexa por defecto, para que un entorno de prueba no
    | compita en Google con el sitio real. TINKU_INDEXABLE=true lo fuerza.
    |
    */

    'indexable' => (bool) env('TINKU_INDEXABLE', env('APP_ENV') === 'production'),

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

    /*
    |--------------------------------------------------------------------------
    | Imágenes
    |--------------------------------------------------------------------------
    |
    | El original se guarda en el disco privado. Cada versión es un WebP sin
    | metadatos, recortado al tamaño exacto ("cover") para su uso.
    |
    */

    'images' => [
        'original_disk' => env('TINKU_IMAGES_ORIGINAL_DISK', 'local'),
        'variants_disk' => env('TINKU_IMAGES_PUBLIC_DISK', 'public'),
        'quality' => 82,
        'max_upload_kb' => 12288,
        'max_megapixels' => 40,
        'collections' => [
            'avatar' => [
                'min' => [200, 200],
                'variants' => [
                    'sm' => [96, 96],
                    'md' => [320, 320],
                ],
            ],
            'cover' => [
                'min' => [1200, 800],
                'variants' => [
                    'card' => [800, 600],
                    'hero' => [1920, 1080],
                    // Redes sociales: LinkedIn y otras no siempre aceptan WebP.
                    'og' => [1200, 630, 'jpg'],
                ],
            ],
        ],
    ],

];
