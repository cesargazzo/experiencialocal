<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\SecurityEvent;
use App\Services\AccountDeletion;
use App\Services\SecurityLog;
use App\Services\TwoFactorGuard;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Derechos sobre los datos personales (Ley 25.326): acceso (descargarlos) y supresión (eliminar la cuenta).
 */
class PersonalDataController extends Controller
{
    public function export(Request $request, SecurityLog $securityLog): Response
    {
        $user = $request->user()->load(['province', 'interestedCategories', 'interestedProvinces', 'hostProfile.experiences', 'termsAcceptances.version']);

        $data = [
            'generado' => now()->toIso8601String(),
            'cuenta' => [
                'nombre' => $user->first_name, 'apellido' => $user->last_name, 'email' => $user->email, 'telefono' => $user->phone,
                'fecha_de_nacimiento' => $user->birth_date?->toDateString(), 'nacionalidad' => $user->nationality_code,
                'pais' => $user->country_code, 'provincia' => $user->province?->name, 'ciudad' => $user->city, 'codigo_postal' => $user->postal_code,
                'alimentacion' => $user->dietary_needs?->map->value->all(), 'alergias' => $user->food_allergies,
                'necesidades' => $user->required_features?->map->value->all(),
                'redes' => $user->socialLinks()->map(fn (array $link) => ['red' => $link['network']->label(), 'enlace' => $link['url'], 'publica' => $link['public']])->all(),
                'nivel_de_verificacion' => $user->verification_level->label(), 'alta' => $user->created_at->toIso8601String(),
                'doble_factor' => $user->hasTwoFactor(),
            ],
            'intereses' => [
                'categorias' => $user->interestedCategories->pluck('name'), 'provincias' => $user->interestedProvinces->pluck('name'),
                'avisos_por_email' => $user->interest_alerts,
            ],
            'verificaciones' => $user->verifications()->get()->map(fn ($v) => [
                'tipo' => $v->type->value, 'estado' => $v->status->value, 'pais_del_documento' => $v->document_country, 'fecha' => $v->submitted_at?->toIso8601String(),
            ]),
            'reservas' => $user->bookings()->with(['experience', 'date'])->get()->map(fn ($b) => [
                'codigo' => $b->code, 'experiencia' => $b->experience->title, 'fecha' => $b->date->starts_at->toIso8601String(),
                'personas' => $b->guests, 'total' => (float) $b->total, 'estado' => $b->status->value, 'mensaje' => $b->guest_note,
            ]),
            'opiniones' => $user->reviews()->with('experience')->get()->map(fn ($r) => [
                'experiencia' => $r->experience->title, 'calificacion' => $r->rating, 'texto' => $r->body, 'fecha' => $r->created_at->toIso8601String(),
            ]),
            'experiencias_como_anfitrion' => $user->hostProfile?->experiences->map(fn ($e) => ['titulo' => $e->title, 'estado' => $e->status->value, 'alta' => $e->created_at->toIso8601String()]) ?? [],
            'mensajes' => Conversation::query()->for($user)->with(['experience', 'messages'])->get()->map(fn ($c) => [
                'experiencia' => $c->experience->title,
                'mensajes' => $c->messages->map(fn ($m) => ['de' => $m->sender_id === $user->id ? 'vos' : 'la otra persona', 'texto' => $m->body, 'fecha' => $m->created_at->toIso8601String()]),
            ]),
            'terminos_aceptados' => $user->termsAcceptances->map(fn ($a) => ['version' => $a->version->version, 'fecha' => $a->accepted_at->toIso8601String(), 'ip' => $a->ip]),
            'accesos_recientes' => SecurityEvent::query()->where('user_id', $user->id)->latest('created_at')->limit(200)->get()->map(fn ($e) => [
                'evento' => $e->label(), 'fecha' => $e->created_at->toIso8601String(), 'ip' => $e->ip,
            ]),
        ];

        $securityLog->record('account.exported', $user);

        return response()->streamDownload(
            fn () => print (json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)),
            'tinku-mis-datos-'.now()->format('Y-m-d').'.json',
            ['Content-Type' => 'application/json; charset=UTF-8'],
        );
    }

    public function destroy(Request $request, AccountDeletion $deletion, TwoFactorGuard $guard): RedirectResponse
    {
        $user = $request->user();
        $request->validateWithBag('deletion', [
            'password' => ['required', 'current_password'],
            'confirm' => ['accepted'],
            'code' => [$user->hasTwoFactor() ? 'required' : 'nullable', 'string'],
        ], [
            'password.current_password' => 'La contraseña no coincide.',
            'confirm.accepted' => 'Confirmá que entendés que no se puede deshacer.',
            'code.required' => 'Escribí el código de tu app de autenticación.',
        ]);

        if ($user->hasTwoFactor()) {
            $guard->attempt($user, $request->string('code')->toString(), 'deletion');
        }
        if ($user->isTeamMember()) {
            return back()->withErrors(['password' => 'Una cuenta administradora no se elimina desde acá: primero quitale el permiso de administración.'], 'deletion');
        }
        if ($blockers = $deletion->blockers($user)) {
            return back()->withErrors(['password' => implode(' ', $blockers)], 'deletion');
        }

        $deletion->delete($user);
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home')->with('status', 'Eliminamos tu cuenta y tus datos personales. Gracias por haber sido parte de Tinku.');
    }
}
