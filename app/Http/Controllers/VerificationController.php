<?php

namespace App\Http\Controllers;

use App\Enums\VerificationProvider;
use App\Enums\VerificationType;
use App\Exceptions\VerificationException;
use App\Models\IdentityVerification;
use App\Services\VerificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Centro de verificación del usuario. Muestra el nivel actual y permite
 * enviar cada tipo de verificación.
 */
class VerificationController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $latest = $user->verifications()->latest('id')->get()->keyBy(fn (IdentityVerification $v) => $v->type->value);

        return view('verification.index', [
            'user' => $user,
            'latest' => $latest,
            'types' => VerificationType::cases(),
        ]);
    }

    /** Confirma el código de email o teléfono. */
    public function confirm(Request $request, VerificationService $verifications): RedirectResponse
    {
        $data = $request->validate([
            'type' => ['required', Rule::in(['email', 'phone'])],
            'code' => ['required', 'digits:6'],
        ]);

        try {
            $verifications->confirmCode($request->user(), VerificationType::from($data['type']), $data['code']);
        } catch (VerificationException $e) {
            return back()->withErrors(['code' => $e->getMessage()]);
        }

        return back()->with('status', $data['type'] === 'email' ? 'Email confirmado.' : 'Teléfono confirmado.');
    }

    public function store(Request $request, VerificationService $verifications): RedirectResponse
    {
        $data = $request->validate([
            'type' => ['required', Rule::enum(VerificationType::class)],
            'document_country' => ['required_if:type,document', 'nullable', 'string', 'size:2'],
            'document_type' => ['required_if:type,document', 'nullable', Rule::in(['dni', 'passport', 'national_id'])],
            'document_number' => ['required_if:type,document', 'nullable', 'string', 'max:40'],
        ]);

        $type = VerificationType::from($data['type']);

        try {
            $verification = $verifications->submit($request->user(), $type, $data);
        } catch (VerificationException $e) {
            return back()->withErrors(['type' => $e->getMessage()]);
        }

        // Fuera de producción no hay proveedor real: los niveles 1 y 2 se aprueban al instante
        // para poder probar el flujo. El nivel 3 siempre espera a un administrador.
        if ($verification->provider === VerificationProvider::Internal) {
            return back()->with('status', 'Te enviamos un código de seis dígitos. Ingresalo para confirmar.');
        }
        if (! app()->isProduction() && $verification->provider !== VerificationProvider::Manual) {
            $verifications->approve($verification, null, ['simulated' => true]);

            return back()->with('status', 'Verificación aprobada (simulada en este entorno).');
        }

        return back()->with('status', $verification->provider === VerificationProvider::Manual
            ? 'Recibimos tu envío. Un administrador lo revisa dentro de las 48 horas.'
            : 'Enviamos tu verificación al proveedor. Te avisamos cuando esté lista.');
    }
}
