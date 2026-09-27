<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
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

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        $policy = PasswordPolicy::current();
        $throttleKey = $this->throttleKey($request);

        if (RateLimiter::tooManyAttempts($throttleKey, $policy->maxLoginAttempts)) {
            $minutes = (int) ceil(RateLimiter::availableIn($throttleKey) / 60);

            return back()
                ->withErrors(['email' => "Hubo demasiados intentos. Probá de nuevo en {$minutes} ".($minutes === 1 ? 'minuto.' : 'minutos.')])
                ->onlyInput('email');
        }

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            RateLimiter::hit($throttleKey, $policy->lockoutMinutes * 60);

            return back()->withErrors(['email' => 'El email o la contraseña no coinciden.'])->onlyInput('email');
        }

        if (Auth::user()->hasExpiredPassword()) {
            Auth::logout();

            return back()->withErrors(['email' => 'La contraseña de única vez venció. Pedí una nueva.'])->onlyInput('email');
        }

        RateLimiter::clear($throttleKey);
        $request->session()->regenerate();

        return redirect()->intended(route('home'))->with('status', 'Hola de nuevo, '.Str::before(Auth::user()->name, ' ').'.');
    }

    public function destroy(Request $request): RedirectResponse
    {
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
