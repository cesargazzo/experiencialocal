<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\SecurityLog;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\View\View;

class ResetPasswordController extends Controller
{
    public function create(Request $request, string $token): View
    {
        return view('auth.reset-password', ['token' => $token, 'email' => $request->query('email')]);
    }

    public function store(Request $request, SecurityLog $securityLog): RedirectResponse
    {
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', PasswordRule::defaults()],
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) use ($securityLog): void {
                $user->changePassword($password);
                $securityLog->record('password.reset', $user, [], $user->email);
                $user->forceFill(['remember_token' => Str::random(60)])->save();

                event(new PasswordReset($user));
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            $securityLog->record('password.reset_failed', null, ['result' => $status], $request->string('email')->toString(), 'warning');

            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => 'El enlace no es válido o venció. Pedí uno nuevo.']);
        }

        return redirect()->route('login')->with('status', 'Listo, ya podés ingresar con tu contraseña nueva.');
    }
}
