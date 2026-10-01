<?php

namespace Tests;

use App\Services\TwoFactorGuard;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // El cliente de pruebas manda "en-us" por defecto; el sitio es en castellano salvo que se pida otro idioma.
        $this->withHeader('Accept-Language', 'es-AR,es;q=0.9');
    }

    /**
     * Una sesión iniciada normalmente: quien tiene doble factor ya ingresó su código.
     * Para simular una sesión sin código (por ejemplo, con "recordarme"), usar be().
     */
    public function actingAs(Authenticatable $user, $guard = null)
    {
        $this->withSession([TwoFactorGuard::SESSION_KEY => now()->timestamp]);

        return parent::actingAs($user, $guard);
    }
}
