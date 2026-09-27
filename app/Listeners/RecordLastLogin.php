<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\DB;

/**
 * Guarda el último ingreso de cada cuenta: con contraseña, al registrarse, al
 * recuperar la contraseña y cuando vuelve con "recordarme".
 */
class RecordLastLogin
{
    public function handle(Login $event): void
    {
        // Directo en la tabla: no es un cambio de datos para la auditoría.
        DB::table('users')->where('id', $event->user->getAuthIdentifier())->update(['last_login_at' => now(), 'last_seen_at' => now()]);
    }
}
