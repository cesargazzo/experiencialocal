<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Services\SecurityLog;
use App\Services\TwoFactorGuard;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Pide el código a quien ya tiene la sesión abierta pero no lo ingresó en ella
 * (por ejemplo, entró con "recordarme" o antes de que se prendiera el doble factor).
 */
class TwoFactorStepUpController extends Controller
{
    public function create(Request $request): View|RedirectResponse
    {
        abort_unless($request->user()->hasTwoFactor(), 404);

        return view('auth.two-factor', ['action' => route('cuenta.2fa.verify'), 'submitLabel' => __('Confirmá')]);
    }

    public function store(Request $request, TwoFactorGuard $guard, SecurityLog $securityLog): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->hasTwoFactor(), 404);

        $request->validate(['code' => ['required', 'string', 'max:20']], ['code.required' => 'Escribí el código.']);
        $method = $guard->attempt($user, $request->string('code')->toString());
        $guard->markPassed($request);
        $securityLog->record('2fa.step_up', $user, ['two_factor' => $method]);

        return redirect()->intended(route('home'));
    }
}
