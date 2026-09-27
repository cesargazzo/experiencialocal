<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\SecurityLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\View\View;

class ForgotPasswordController extends Controller
{
    public function create(): View
    {
        return view('auth.forgot-password');
    }

    public function store(Request $request, SecurityLog $securityLog): RedirectResponse
    {
        $request->validate(['email' => ['required', 'email']]);

        $status = Password::sendResetLink($request->only('email'));
        $securityLog->record(
            'password.reset_requested',
            User::query()->where('email', $request->string('email')->lower()->toString())->first(),
            ['account_exists' => $status !== Password::INVALID_USER, 'result' => $status],
            $request->string('email')->toString(),
            $status === Password::INVALID_USER ? 'warning' : 'info',
        );

        // Mismo mensaje exista o no la cuenta, para no revelar qué emails están registrados.
        return back()->with('status', 'Si hay una cuenta con ese email, te mandamos un enlace para elegir una contraseña nueva.');
    }
}
