<?php

namespace App\Http\Controllers\Admin;

use App\Enums\VerificationLevel;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Province;
use App\Models\SecurityEvent;
use App\Models\User;
use App\Services\SecurityLog;
use App\Services\VerificationService;
use App\Support\CountryList;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
            'dni' => ['nullable', 'string', 'max:40'],
            'nivel' => ['nullable', Rule::in(['0', '1', '2', '3'])],
            'rol' => ['nullable', Rule::in(['admin', 'anfitrion', 'suspendida'])],
            'provincia' => ['nullable', 'integer', 'exists:provinces,id'],
            'alta' => ['nullable', Rule::in(array_keys(self::SIGNUP_PERIODS))],
            'orden' => ['nullable', Rule::in(array_keys(self::SORTS))],
        ]);

        $users = User::query()
            ->with(['avatar', 'hostProfile', 'province'])
            ->when($filters['q'] ?? null, fn ($q, $term) => $q->where(fn ($w) => $w->where('name', 'ilike', "%{$term}%")->orWhere('email', 'ilike', "%{$term}%")))
            ->when($filters['dni'] ?? null, fn ($q, $number) => $q->whereHas('verifications', fn ($v) => $v->whereIn('document_hash', $this->documentHashesFor($number))))
            ->when(isset($filters['nivel']), fn ($q) => $q->where('verification_level', (int) $filters['nivel']))
            ->when(($filters['rol'] ?? null) === 'admin', fn ($q) => $q->where('is_admin', true))
            ->when(($filters['rol'] ?? null) === 'anfitrion', fn ($q) => $q->has('hostProfile'))
            ->when(($filters['rol'] ?? null) === 'suspendida', fn ($q) => $q->whereNotNull('suspended_at'))
            ->when($filters['provincia'] ?? null, fn ($q, $provinceId) => $q->where('province_id', $provinceId))
            ->when($filters['alta'] ?? null, fn ($q, $period) => $q->where('created_at', '>=', now()->subDays(self::SIGNUP_PERIODS[$period]['days'])))
            ->when(
                ($filters['orden'] ?? 'alta') === 'ingreso',
                fn ($q) => $q->orderByRaw('last_login_at desc nulls last'),
                fn ($q) => ($filters['orden'] ?? null) === 'nombre' ? $q->orderBy('name') : $q->latest(),
            )
            ->orderByDesc('id')
            ->paginate(30)
            ->withQueryString();

        return view('admin.users.index', [
            'users' => $users,
            'filters' => $filters,
            'levels' => VerificationLevel::cases(),
            'provinces' => Province::query()->orderBy('name')->get(['id', 'name']),
            'signupPeriods' => self::SIGNUP_PERIODS,
            'sorts' => self::SORTS,
        ]);
    }

    /** @var array<string, array{label: string, days: int}> */
    private const SIGNUP_PERIODS = [
        'hoy' => ['label' => 'Últimas 24 horas', 'days' => 1],
        'semana' => ['label' => 'Últimos 7 días', 'days' => 7],
        'mes' => ['label' => 'Últimos 30 días', 'days' => 30],
        'trimestre' => ['label' => 'Últimos 90 días', 'days' => 90],
    ];

    /** @var array<string, string> */
    private const SORTS = [
        'alta' => 'Últimos registrados',
        'ingreso' => 'Último ingreso',
        'nombre' => 'Nombre',
    ];

    /**
     * El documento se guarda como huella con su país. Sin saber el país, se prueba con todos.
     *
     * @return list<string>
     */
    private function documentHashesFor(string $number): array
    {
        return array_map(fn (string $country): string => VerificationService::documentHash($country, $number), CountryList::codes());
    }

    public function show(User $user): View
    {
        $user->load(['avatar', 'hostProfile.plan', 'verifications' => fn ($q) => $q->latest(), 'verifications.reviewer', 'invitedBy', 'province', 'termsAcceptances' => fn ($q) => $q->with('version')->latest('accepted_at')]);

        return view('admin.users.show', [
            'user' => $user,
            'events' => SecurityEvent::query()->where('user_id', $user->id)->orWhere('email', $user->email)->latest('created_at')->limit(30)->get(),
            'levels' => VerificationLevel::cases(),
            'auditLogs' => AuditLog::query()->with('user')
                ->where(fn ($q) => $q->where('auditable_type', User::class)->where('auditable_id', (string) $user->id))
                ->orWhere('user_id', $user->id)
                ->latest('created_at')->latest('id')->limit(20)->get(),
        ]);
    }

    /** Validar a mano una o varias cuentas hasta un nivel. */
    public function validateLevel(Request $request, VerificationService $verifications, SecurityLog $securityLog): RedirectResponse
    {
        $data = $request->validate([
            'users' => ['required', 'array', 'min:1', 'max:100'],
            'users.*' => ['integer', 'exists:users,id'],
            'level' => ['required', Rule::in(['1', '2', '3'])],
            'reason' => ['required', 'string', 'min:5', 'max:300'],
        ], [
            'users.required' => 'Elegí al menos una cuenta.',
            'reason.required' => 'Contá el motivo: queda registrado.',
        ]);

        $level = VerificationLevel::from((int) $data['level']);
        $updated = 0;

        User::query()->whereIn('id', $data['users'])->get()->each(function (User $user) use ($verifications, $securityLog, $level, $data, $request, &$updated) {
            $before = $user->verification_level;
            $after = $verifications->grantLevelManually($user, $level, $request->user(), $data['reason']);

            if ($after !== $before) {
                $updated++;
                $securityLog->record('user.validated_manually', $request->user(), [
                    'account' => $user->email, 'account_id' => $user->id, 'before' => $before->value, 'after' => $after->value, 'reason' => $data['reason'],
                ], null, 'warning');
            }
        });

        return back()->with('status', $updated === 1 ? 'Validamos 1 cuenta.' : "Validamos {$updated} cuentas.");
    }

    public function toggleSuspension(Request $request, User $user, SecurityLog $securityLog): RedirectResponse
    {
        abort_if($user->is($request->user()), 422, 'No podés suspender tu propia cuenta.');

        $data = $request->validate(['reason' => [$user->isSuspended() ? 'nullable' : 'required', 'string', 'max:300']], ['reason.required' => 'Contá el motivo de la suspensión.']);

        if ($user->isSuspended()) {
            $user->forceFill(['suspended_at' => null, 'suspension_reason' => null])->save();
            $securityLog->record('user.unsuspended', $request->user(), ['account' => $user->email, 'account_id' => $user->id], null, 'warning');

            return back()->with('status', 'Reactivamos la cuenta.');
        }

        $user->forceFill(['suspended_at' => now(), 'suspension_reason' => $data['reason']])->save();
        $securityLog->record('user.suspended', $request->user(), ['account' => $user->email, 'account_id' => $user->id, 'reason' => $data['reason']], null, 'danger');

        return back()->with('status', 'Suspendimos la cuenta. Pierde la sesión en su próximo paso.');
    }
}
