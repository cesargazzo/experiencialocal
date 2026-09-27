<?php

namespace App\Http\Controllers\Auth;

use App\Enums\VerificationType;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\VerificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class RegisterController extends Controller
{
    public function create(): View
    {
        return view('auth.register');
    }

    public function store(Request $request, VerificationService $verifications): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['required', 'string', 'max:32'],
            'nationality_code' => ['required', 'string', 'size:2'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $user = User::create([...$data, 'country_code' => $data['nationality_code']]);

        // Nivel 1 arranca acá: se envían los códigos de email y teléfono.
        $verifications->submit($user, VerificationType::Email);
        $verifications->submit($user, VerificationType::Phone);

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('verificacion')->with('status', 'Cuenta creada. Confirmá tu email y tu teléfono para empezar.');
    }
}
