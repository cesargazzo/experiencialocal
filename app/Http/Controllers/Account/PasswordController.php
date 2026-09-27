<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class PasswordController extends Controller
{
    public function edit(Request $request): View
    {
        return view('account.password', ['forced' => $request->user()->must_change_password]);
    }

    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', 'different:current_password', Password::defaults()],
        ], [
            'current_password.current_password' => 'La contraseña actual no coincide.',
            'password.different' => 'Elegí una contraseña distinta de la actual.',
        ]);

        $request->user()->changePassword($request->string('password')->toString());
        $request->session()->regenerate();

        return redirect()->intended(route('home'))->with('status', 'Listo, cambiaste tu contraseña.');
    }
}
