<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\SecurityLog;
use App\Services\TwoFactorGuard;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Segundo paso del ingreso: con la contraseña correcta, falta el código de la app.
 */
class TwoFactorChallengeController extends Controller
{
    /** Minutos que dura el paso intermedio antes de tener que ingresar de nuevo. */
    public const TTL_MINUTES = 5;

    public function create(Request $request): View|RedirectResponse
    {
        return $this->pendingUser($request) ? view('auth.two-factor') : redirect()->route('login');
    }

    public function store(Request $request, SecurityLog $securityLog, TwoFactorGuard $guard): RedirectResponse
    {
        $user = $this->pendingUser($request);
        if (! $user) {
            return redirect()->route('login')->withErrors(['email' => __('Pasó demasiado tiempo. Ingresá de nuevo.')]);
        }
        if ($user->isSuspended()) {
            $request->session()->forget(['login.2fa.id', 'login.2fa.at', 'login.2fa.remember']);

            return redirect()->route('login')->withErrors(['email' => __('Tu cuenta está suspendida. Escribinos para revisarla.')]);
        }

        $request->validate(['code' => ['required', 'string', 'max:20']], ['code.required' => __('Escribí el código.')]);
        $method = $guard->attempt($user, $request->string('code')->toString());

        $remember = (bool) $request->session()->pull('login.2fa.remember', false);
        $request->session()->forget(['login.2fa.id', 'login.2fa.at']);
        Auth::login($user, $remember);
        $request->session()->regenerate();
        $guard->markPassed($request);
        $securityLog->record('login.succeeded', $user, ['remember' => $remember, 'two_factor' => $method], $user->email);

        return redirect()->intended(route('home'))->with('status', $method === 'recovery'
            ? __('Usaste un código de recuperación. Te quedan :count.', ['count' => count($user->two_factor_recovery_codes ?? [])])
            : __('Hola de nuevo, :name.', ['name' => Str::before($user->name, ' ')]));
    }

    private function pendingUser(Request $request): ?User
    {
        $id = $request->session()->get('login.2fa.id');
        $at = (int) $request->session()->get('login.2fa.at');

        if (! User::twoFactorAvailable()) {
            return null;
        }

        $user = $id && $at > now()->subMinutes(self::TTL_MINUTES)->timestamp ? User::find($id) : null;

        return $user?->hasTwoFactor() ? $user : null;
    }
}
