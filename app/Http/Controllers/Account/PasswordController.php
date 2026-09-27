<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Services\SecurityLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class PasswordController extends Controller
{
    public function edit(Request $request): View
    {
        return view('account.security', ['forced' => $request->user()->must_change_password]);
    }

    public function update(Request $request, SecurityLog $securityLog): RedirectResponse
    {
        $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', 'different:current_password', Password::defaults()],
        ], [
            'current_password.current_password' => 'La contraseña actual no coincide.',
            'password.different' => 'Elegí una contraseña distinta de la actual.',
        ]);

        $wasForced = $request->user()->must_change_password;
        $request->user()->changePassword($request->string('password')->toString());
        $securityLog->record('password.changed', $request->user(), ['forced' => $wasForced]);
        $request->session()->regenerate();

        return redirect()->intended(route('home'))->with('status', 'Listo, cambiaste tu contraseña.');
    }
}
