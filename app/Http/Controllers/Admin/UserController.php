<?php

namespace App\Http\Controllers\Admin;

use App\Enums\VerificationLevel;
use App\Exceptions\BookingException;
use App\Exceptions\VerificationException;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Booking;
use App\Models\IdentityVerification;
use App\Models\Province;
use App\Models\SecurityEvent;
use App\Models\User;
use App\Services\BookingService;
use App\Services\SecurityLog;
use App\Services\VerificationService;
use App\Support\CountryList;
use App\Support\PrivateHash;
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
            'nivel' => ['nullable', Rule::in(['0', '1', '2', '3', 'verificada'])],
            'rol' => ['nullable', Rule::in(['admin', 'anfitrion', 'suspendida'])],
            'provincia' => ['nullable', 'integer', 'exists:provinces,id'],
            'alta' => ['nullable', Rule::in(array_keys(self::SIGNUP_PERIODS))],
            'ingreso' => ['nullable', Rule::in([...array_keys(self::LOGIN_PERIODS), 'nunca'])],
            'orden' => ['nullable', Rule::in(array_keys(self::SORTS))],
            'cumple' => ['nullable', Rule::in(['hoy', 'semana'])],
        ]);

        $today = now(config('tinku.timezone'))->startOfDay();
        $week = [$today->copy()->startOfWeek(), $today->copy()->endOfWeek()];

        $users = User::query()
            ->with(['avatar', 'hostProfile', 'province'])
            ->when($filters['q'] ?? null, fn ($q, $term) => $q->where(fn ($w) => $w->where('name', 'ilike', "%{$term}%")->orWhere('email', 'ilike', "%{$term}%")
                // El teléfono está cifrado: se busca por número exacto con su huella.
                ->when(PrivateHash::phone($term), fn ($w, $phoneHash) => $w->orWhere('phone_hash', $phoneHash))))
            ->when($filters['dni'] ?? null, fn ($q, $number) => $q->whereHas('verifications', fn ($v) => $v->whereIn('document_hash', $this->documentHashesFor($number))))
            ->when(($filters['nivel'] ?? null) === 'verificada', fn ($q) => $q->where('verification_level', '>=', VerificationLevel::Document->value))
            ->when(isset($filters['nivel']) && $filters['nivel'] !== 'verificada', fn ($q) => $q->where('verification_level', (int) $filters['nivel']))
            ->when(($filters['rol'] ?? null) === 'admin', fn ($q) => $q->whereNotNull('team_role'))
            ->when(($filters['rol'] ?? null) === 'anfitrion', fn ($q) => $q->has('hostProfile'))
            ->when(($filters['rol'] ?? null) === 'suspendida', fn ($q) => $q->whereNotNull('suspended_at'))
            ->when($filters['provincia'] ?? null, fn ($q, $provinceId) => $q->where('province_id', $provinceId))
            ->when(($filters['cumple'] ?? null) === 'hoy', fn ($q) => $q->birthdayBetween($today, $today))
            ->when(($filters['cumple'] ?? null) === 'semana', fn ($q) => $q->birthdayBetween(...$week))
            ->when($filters['alta'] ?? null, fn ($q, $period) => $q->where('created_at', '>=', now()->subDays(self::SIGNUP_PERIODS[$period]['days'])))
            ->when(($filters['ingreso'] ?? null) === 'nunca', fn ($q) => $q->whereNull('last_login_at')->whereNull('last_seen_at'))
            ->when(isset(self::LOGIN_PERIODS[$filters['ingreso'] ?? '']), fn ($q) => $q->activeSince(now()->subDays(self::LOGIN_PERIODS[$filters['ingreso']]['days'])))
            ->when(
                ($filters['orden'] ?? (isset($filters['ingreso']) ? 'ingreso' : 'alta')) === 'ingreso',
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
            'loginPeriods' => self::LOGIN_PERIODS,
            'sorts' => self::SORTS,
            'kpis' => $this->kpis($filters),
            'today' => $today,
            'weekStart' => $week[0],
            'sharedDocuments' => app(VerificationService::class)->accountsSharingDocuments(),
            'birthdaysThisWeek' => User::query()->with('avatar')->birthdayBetween(...$week)->get()
                ->sortBy(fn (User $user) => $user->birthdayOnOrAfter($week[0])->timestamp)
                ->values(),
        ]);
    }

    /** @var array<string, array{label: string, days: int}> */
    private const SIGNUP_PERIODS = [
        'hoy' => ['label' => 'Últimas 24 horas', 'days' => 1],
        'semana' => ['label' => 'Últimos 7 días', 'days' => 7],
        'mes' => ['label' => 'Últimos 30 días', 'days' => 30],
        'trimestre' => ['label' => 'Últimos 90 días', 'days' => 90],
    ];

    /** @var array<string, array{label: string, days: int}> */
    private const LOGIN_PERIODS = [
        'hoy' => ['label' => 'Últimas 24 horas', 'days' => 1],
        '5dias' => ['label' => 'Últimos 5 días', 'days' => 5],
        'mes' => ['label' => 'Últimos 30 días', 'days' => 30],
    ];

    /**
     * Números de arriba. Cada uno es un atajo a la lista filtrada.
     *
     * @param  array<string, mixed>  $filters
     * @return list<array{label: string, value: int, query: array<string, string>, active: bool}>
     */
    private function kpis(array $filters): array
    {
        $since = now()->subDays(self::LOGIN_PERIODS['5dias']['days']);
        $counts = User::query()->toBase()
            ->selectRaw('count(*) as total')
            ->selectRaw('count(*) filter (where last_login_at >= ? or last_seen_at >= ?) as active', [$since, $since])
            ->selectRaw('count(*) filter (where created_at >= ?) as recent', [now()->subDays(self::SIGNUP_PERIODS['semana']['days'])])
            ->selectRaw('count(*) filter (where verification_level >= ?) as verified', [VerificationLevel::Document->value])
            ->selectRaw('count(*) filter (where exists (select 1 from host_profiles where host_profiles.user_id = users.id)) as hosts')
            ->selectRaw('count(*) filter (where suspended_at is not null) as suspended')
            ->first();

        $active = array_filter($filters, fn ($value) => $value !== null && $value !== '');
        $tile = fn (string $label, int $value, array $query): array => [
            'label' => $label, 'value' => $value, 'query' => $query,
            'active' => $query !== [] && array_intersect_assoc($active, $query) === $query,
        ];

        return [
            [...$tile('Cuentas', (int) $counts->total, []), 'active' => $active === []],
            $tile('Ingresaron en los últimos 5 días', (int) $counts->active, ['ingreso' => '5dias']),
            $tile('Nuevas en los últimos 7 días', (int) $counts->recent, ['alta' => 'semana']),
            $tile('Con identidad verificada', (int) $counts->verified, ['nivel' => 'verificada']),
            $tile('Anfitriones', (int) $counts->hosts, ['rol' => 'anfitrion']),
            $tile('Suspendidas', (int) $counts->suspended, ['rol' => 'suspendida']),
        ];
    }

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

    public function show(Request $request, User $user, SecurityLog $securityLog): View
    {
        // Ver los datos personales de una cuenta queda registrado: quién, cuándo y de quién.
        $securityLog->record('admin.user_viewed', $request->user(), ['account' => $user->email, 'account_id' => $user->id]);

        $user->load(['avatar', 'hostProfile.plan', 'verifications' => fn ($q) => $q->latest(), 'verifications.reviewer', 'invitedBy', 'province', 'termsAcceptances' => fn ($q) => $q->with('version')->latest('accepted_at')]);

        return view('admin.users.show', [
            'user' => $user,
            'events' => SecurityEvent::query()->where('user_id', $user->id)->orWhere('email', $user->email)->latest('created_at')->limit(30)->get(),
            'levels' => VerificationLevel::cases(),
            'bookings' => $user->bookings()->with(['date', 'experience'])->latest()->limit(20)->get(),
            'samePhone' => $user->phone_hash ? User::query()->where('phone_hash', $user->phone_hash)->whereKeyNot($user->getKey())->get(['id', 'name', 'email']) : collect(),
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
            'document_country' => ['nullable', Rule::in(CountryList::codes())],
            'document_number' => ['nullable', 'string', 'max:40'],
        ], [
            'users.required' => 'Elegí al menos una cuenta.',
            'reason.required' => 'Contá el motivo: queda registrado.',
        ]);

        $level = VerificationLevel::from((int) $data['level']);
        // El número de documento solo tiene sentido validando una cuenta por vez.
        $documentNumber = count($data['users']) === 1 ? ($data['document_number'] ?? null) : null;
        $updated = 0;
        $problems = [];

        User::query()->whereIn('id', $data['users'])->get()->each(function (User $user) use ($verifications, $securityLog, $level, $data, $documentNumber, $request, &$updated, &$problems) {
            $before = $user->verification_level;

            try {
                $after = $verifications->grantLevelManually($user, $level, $request->user(), $data['reason'], $data['document_country'] ?? 'AR', $documentNumber);
            } catch (VerificationException $e) {
                $problems[] = "{$user->name}: {$e->getMessage()}";
                $securityLog->record('user.validation_blocked', $request->user(), ['account' => $user->email, 'account_id' => $user->id, 'level' => $level->value, 'reason' => $e->getMessage()], null, 'warning');

                return;
            }

            if ($after !== $before) {
                $updated++;
                $securityLog->record('user.validated_manually', $request->user(), [
                    'account' => $user->email, 'account_id' => $user->id, 'before' => $before->value, 'after' => $after->value, 'reason' => $data['reason'],
                ], null, 'warning');
            }
        });

        $response = back()->with('status', $updated === 1 ? 'Validamos 1 cuenta.' : "Validamos {$updated} cuentas.");

        return $problems ? $response->withErrors(['users' => $problems]) : $response;
    }

    public function revokeVerification(Request $request, User $user, IdentityVerification $verification, VerificationService $verifications, SecurityLog $securityLog): RedirectResponse
    {
        abort_unless($verification->user_id === $user->id, 404);

        $data = $request->validate(['reason' => ['required', 'string', 'min:5', 'max:300']], ['reason.required' => 'Contá por qué la revocás.']);

        try {
            $verifications->revoke($verification, $data['reason'], $request->user());
        } catch (VerificationException $e) {
            return back()->withErrors(['revoke' => $e->getMessage()]);
        }

        $securityLog->record('verification.revoked', $request->user(), [
            'account' => $user->email, 'account_id' => $user->id, 'verification_id' => $verification->id, 'type' => $verification->type->value, 'reason' => $data['reason'],
        ], null, 'warning');

        return back()->with('status', 'Revocamos la verificación. La cuenta quedó en '.$user->fresh()->verification_level->label().'.');
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

    /** Una ausencia mal marcada por el anfitrión: la reserva vuelve a contar como realizada. */
    public function revertNoShow(Request $request, User $user, Booking $booking, BookingService $bookings, SecurityLog $securityLog): RedirectResponse
    {
        abort_unless($booking->user_id === $user->id, 404);
        $data = $request->validateWithBag('noShow', ['reason' => ['required', 'string', 'min:5', 'max:300']], ['reason.required' => 'Contá qué revisaste.']);

        try {
            $bookings->revertNoShow($booking);
        } catch (BookingException $e) {
            return back()->withErrors(['booking' => $e->getMessage()]);
        }
        $securityLog->record('booking.no_show_reverted', $request->user(), ['account' => $user->email, 'account_id' => $user->id, 'booking' => $booking->code, 'reason' => $data['reason']], null, 'warning');

        return back()->with('status', "Revertimos la ausencia de la reserva {$booking->code}. Le avisamos.");
    }
}
