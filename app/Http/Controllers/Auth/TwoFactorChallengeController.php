<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\SecurityLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
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

    public function store(Request $request, SecurityLog $securityLog): RedirectResponse
    {
        $user = $this->pendingUser($request);
        if (! $user) {
            return redirect()->route('login')->withErrors(['email' => 'Pasó demasiado tiempo. Ingresá de nuevo.']);
        }

        $request->validate(['code' => ['required', 'string', 'max:20']], ['code.required' => 'Escribí el código.']);

        $key = '2fa:'.$user->id;
        if (RateLimiter::tooManyAttempts($key, 5)) {
            $securityLog->record('login.locked', $user, ['step' => '2fa'], $user->email, 'danger');

            return back()->withErrors(['code' => 'Hubo demasiados intentos. Esperá un minuto y probá de nuevo.']);
        }

        $method = $user->verifyTwoFactorCode($request->string('code')->toString());
        if (! $method) {
            RateLimiter::hit($key, 60);
            $securityLog->record('2fa.failed', $user, [], $user->email, 'warning');

            return back()->withErrors(['code' => 'El código no coincide.']);
        }

        RateLimiter::clear($key);
        $remember = (bool) $request->session()->pull('login.2fa.remember', false);
        $request->session()->forget(['login.2fa.id', 'login.2fa.at']);
        Auth::login($user, $remember);
        $request->session()->regenerate();
        $securityLog->record('login.succeeded', $user, ['remember' => $remember, 'two_factor' => $method], $user->email);
        if ($method === 'recovery') {
            $securityLog->record('2fa.recovery_used', $user, ['left' => count($user->two_factor_recovery_codes ?? [])], $user->email, 'warning');
        }

        return redirect()->intended(route('home'))->with('status', $method === 'recovery'
            ? 'Usaste un código de recuperación. Te quedan '.count($user->two_factor_recovery_codes ?? []).'.'
            : 'Hola de nuevo, '.Str::before($user->name, ' ').'.');
    }

    private function pendingUser(Request $request): ?User
    {
        $id = $request->session()->get('login.2fa.id');
        $at = (int) $request->session()->get('login.2fa.at');

        if (! User::twoFactorAvailable()) {
            return null;
        }

        return $id && $at > now()->subMinutes(self::TTL_MINUTES)->timestamp ? User::find($id) : null;
    }
}
