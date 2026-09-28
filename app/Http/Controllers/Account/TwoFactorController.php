<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\SecurityLog;
use App\Support\Totp;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Activar, confirmar y desactivar el doble factor (código de una app de autenticación).
 */
class TwoFactorController extends Controller
{
    /** Genera un secreto nuevo, todavía sin confirmar: no se exige hasta ingresar el primer código. */
    public function start(Request $request): RedirectResponse
    {
        abort_unless(User::twoFactorAvailable(), 404);
        $request->validateWithBag('twoFactor', ['password' => ['required', 'current_password']], ['password.current_password' => 'La contraseña no coincide.']);

        $request->user()->forceFill([
            'two_factor_secret' => Totp::generateSecret(),
            'two_factor_confirmed_at' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_last_step' => null,
        ])->save();

        return redirect()->to(route('cuenta.seguridad').'#doble-factor');
    }

    public function confirm(Request $request, SecurityLog $securityLog): RedirectResponse
    {
        abort_unless(User::twoFactorAvailable(), 404);
        $user = $request->user();
        $request->validateWithBag('twoFactor', ['code' => ['required', 'string']], ['code.required' => 'Escribí el código de 6 dígitos de la app.']);

        $step = $user->two_factor_secret ? Totp::verify($user->two_factor_secret, $request->string('code')->toString()) : null;
        if ($step === null) {
            return back()->withErrors(['code' => 'El código no coincide. Revisá que la hora del celular esté bien y probá con el código nuevo.'], 'twoFactor');
        }

        $user->forceFill(['two_factor_confirmed_at' => now(), 'two_factor_last_step' => $step])->save();
        $codes = $user->regenerateRecoveryCodes();
        $securityLog->record('2fa.enabled', $user);

        return redirect()->to(route('cuenta.seguridad').'#doble-factor')
            ->with('recovery_codes', $codes)
            ->with('status', 'Activaste el doble factor.');
    }

    public function recoveryCodes(Request $request, SecurityLog $securityLog): RedirectResponse
    {
        abort_unless(User::twoFactorAvailable(), 404);
        $request->validateWithBag('twoFactor', ['password' => ['required', 'current_password']], ['password.current_password' => 'La contraseña no coincide.']);
        abort_unless($request->user()->hasTwoFactor(), 404);

        $codes = $request->user()->regenerateRecoveryCodes();
        $securityLog->record('2fa.recovery_regenerated', $request->user());

        return redirect()->to(route('cuenta.seguridad').'#doble-factor')->with('recovery_codes', $codes);
    }

    public function destroy(Request $request, SecurityLog $securityLog): RedirectResponse
    {
        abort_unless(User::twoFactorAvailable(), 404);
        $user = $request->user();
        $request->validateWithBag('twoFactor', ['password' => ['required', 'current_password'], 'code' => ['required', 'string']], [
            'password.current_password' => 'La contraseña no coincide.',
            'code.required' => 'Escribí un código de la app o uno de recuperación.',
        ]);

        if ($user->hasTwoFactor() && ! $user->verifyTwoFactorCode($request->string('code')->toString())) {
            return back()->withErrors(['code' => 'El código no coincide.'], 'twoFactor');
        }

        $user->forceFill(['two_factor_secret' => null, 'two_factor_recovery_codes' => null, 'two_factor_confirmed_at' => null, 'two_factor_last_step' => null])->save();
        $securityLog->record('2fa.disabled', $user, [], null, 'warning');

        return redirect()->to(route('cuenta.seguridad').'#doble-factor')->with('status', $user->isAdmin()
            ? 'Desactivaste el doble factor. Para volver a la administración vas a tener que activarlo.'
            : 'Desactivaste el doble factor.');
    }
}
