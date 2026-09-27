<?php

namespace App\Http\Controllers\Auth;

use App\Enums\VerificationType;
use App\Http\Controllers\Controller;
use App\Models\Invitation;
use App\Models\User;
use App\Services\SecurityLog;
use App\Services\VerificationService;
use App\Support\PlatformSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class RegisterController extends Controller
{
    public function create(Request $request): View
    {
        $invitation = Invitation::findUsableByToken((string) $request->session()->get('invitation_token'));

        return view('auth.register', ['invitation' => $invitation?->load('inviter')]);
    }

    public function store(Request $request, VerificationService $verifications, SecurityLog $securityLog): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['required', 'string', 'max:32'],
            'birth_date' => ['required', ...User::birthDateRules()],
            'nationality_code' => ['required', 'string', 'size:2'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ], [
            'birth_date.before_or_equal' => 'Tenés que tener al menos '.config('tinku.min_age').' años para usar Tinku.',
            'birth_date.after' => 'Revisá la fecha de nacimiento.',
        ]);

        $user = User::create([...$data, 'country_code' => $data['nationality_code']]);
        $securityLog->record('register', $user, ['nationality' => $user->nationality_code], $user->email);

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
