<?php

namespace App\Http\Controllers\Auth;

use App\Enums\VerificationType;
use App\Http\Controllers\Controller;
use App\Models\Invitation;
use App\Models\TermsAcceptance;
use App\Models\TermsVersion;
use App\Models\User;
use App\Services\SecurityLog;
use App\Services\VerificationService;
use App\Support\CountryList;
use App\Support\PlatformSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class RegisterController extends Controller
{
    public function create(Request $request): View
    {
        $invitation = Invitation::findUsableByToken((string) $request->session()->get('invitation_token'));

        return view('auth.register', ['invitation' => $invitation?->load('inviter'), 'terms' => TermsVersion::current()]);
    }

    public function store(Request $request, VerificationService $verifications, SecurityLog $securityLog): RedirectResponse
    {
        $terms = TermsVersion::current();
        $request->validate($terms ? [
            'terms_version_id' => ['required', 'integer', 'in:'.$terms->id],
            'accept_terms' => ['accepted'],
        ] : [], [
            'accept_terms.accepted' => 'Para crear tu cuenta tenés que aceptar los términos y condiciones.',
            'terms_version_id.in' => 'Los términos cambiaron mientras completabas el formulario. Revisá la versión nueva.',
        ]);

        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:60'],
            'last_name' => ['required', 'string', 'max:80'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['required', 'string', 'max:32'],
            'birth_date' => ['required', ...User::birthDateRules()],
            'nationality_code' => ['required', Rule::in(CountryList::codes())],
            'password' => ['required', 'confirmed', Password::defaults()],
        ], [
            'birth_date.before_or_equal' => 'Tenés que tener al menos '.config('tinku.min_age').' años para usar Tinku.',
            'birth_date.after' => 'Revisá la fecha de nacimiento.',
        ]);

        $user = User::create([...$data, 'country_code' => $data['nationality_code']]);
        $securityLog->record('register', $user, ['nationality' => $user->nationality_code], $user->email);
        if ($terms) {
            TermsAcceptance::record($user, $terms, $request, 'register');
        }

        $invitation = Invitation::findUsableByToken((string) $request->session()->pull('invitation_token'));
        if ($invitation) {
            $invitation->forceFill(['accepted_by' => $user->id, 'accepted_at' => now()])->save();
            $user->forceFill(['invited_by' => $invitation->inviter_id])->save();
            $securityLog->record('invitation.accepted', $user, ['invitation_id' => $invitation->id, 'inviter_id' => $invitation->inviter_id], $user->email);
        }

        // Nivel 1 arranca acá: se envía el código de email, y el de teléfono si hay SMS.
        $verifications->submit($user, VerificationType::Email);
        if (PlatformSettings::current()->smsVerification) {
            $verifications->submit($user, VerificationType::Phone);
        }

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('verificacion')->with('status', PlatformSettings::current()->smsVerification
            ? 'Cuenta creada. Confirmá tu email y tu teléfono para empezar.'
            : 'Cuenta creada. Confirmá tu email para empezar.');
    }
}
