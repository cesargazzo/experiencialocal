<?php

namespace Tests;

use App\Services\TwoFactorGuard;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
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
