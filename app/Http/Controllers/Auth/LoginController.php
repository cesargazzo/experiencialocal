<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\SecurityLog;
use App\Support\PasswordPolicy;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    public function store(Request $request, SecurityLog $securityLog): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        $policy = PasswordPolicy::current();
        $throttleKey = $this->throttleKey($request);

        if (RateLimiter::tooManyAttempts($throttleKey, $policy->maxLoginAttempts)) {
            $minutes = (int) ceil(RateLimiter::availableIn($throttleKey) / 60);
            $securityLog->record('login.locked', null, ['minutes_left' => $minutes], $credentials['email'], 'danger');

            return back()
                ->withErrors(['email' => __('Hubo demasiados intentos. Probá de nuevo en :time.', ['time' => plural_es($minutes, __('minuto'), __('minutos'))])])
                ->onlyInput('email');
        }

        // Se valida la contraseña sin iniciar sesión todavía: con doble factor falta el código.
        if (! Auth::validate($credentials)) {
            RateLimiter::hit($throttleKey, $policy->lockoutMinutes * 60);
            $securityLog->record('login.failed', null, ['attempts' => RateLimiter::attempts($throttleKey)], $credentials['email'], 'warning');

            return back()->withErrors(['email' => __('El email o la contraseña no coinciden.')])->onlyInput('email');
        }

        /** @var User $user */
        $user = Auth::getProvider()->retrieveByCredentials($credentials);

        if ($user->isSuspended()) {
            $securityLog->record('login.failed', $user, ['reason' => 'cuenta suspendida'], $credentials['email'], 'warning');

            return back()->withErrors(['email' => __('Tu cuenta está suspendida. Escribinos para revisarla.')])->onlyInput('email');
        }

        if ($user->hasExpiredPassword()) {
            $securityLog->record('login.failed', $user, ['reason' => 'contraseña de única vez vencida'], $credentials['email'], 'warning');

            return back()->withErrors(['email' => __('La contraseña de única vez venció. Pedí una nueva.')])->onlyInput('email');
        }

        RateLimiter::clear($throttleKey);

        if ($user->hasTwoFactor()) {
            // Sesión nueva ya en el paso intermedio: una sesión fijada de antemano no sirve para completarlo.
            $request->session()->regenerate();
            $request->session()->put(['login.2fa.id' => $user->id, 'login.2fa.at' => now()->timestamp, 'login.2fa.remember' => $request->boolean('remember')]);

            return redirect()->route('login.2fa');
        }

        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();
        $securityLog->record('login.succeeded', $user, ['remember' => $request->boolean('remember')], $credentials['email']);

        return redirect()->intended(route('home'))->with('status', __('Hola de nuevo, :name.', ['name' => Str::before($user->name, ' ')]));
    }

    public function destroy(Request $request, SecurityLog $securityLog): RedirectResponse
    {
        $securityLog->record('logout', $request->user());
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }

    /** Los intentos se cuentan por cuenta y por dirección IP. */
    private function throttleKey(Request $request): string
    {
        return 'login:'.Str::transliterate(Str::lower($request->string('email')->toString())).'|'.$request->ip();
    }
}
