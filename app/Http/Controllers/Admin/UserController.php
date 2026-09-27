<?php

namespace App\Http\Controllers\Admin;

use App\Enums\VerificationLevel;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\SecurityEvent;
use App\Models\User;
use App\Services\SecurityLog;
use App\Services\VerificationService;
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
            'nivel' => ['nullable', Rule::in(['0', '1', '2', '3'])],
            'rol' => ['nullable', Rule::in(['admin', 'anfitrion', 'suspendida'])],
        ]);

        $users = User::query()
            ->with(['avatar', 'hostProfile'])
            ->withMax(['securityEvents as last_login_at' => fn ($q) => $q->where('type', 'login.succeeded')], 'created_at')
            ->when($filters['q'] ?? null, fn ($q, $term) => $q->where(fn ($w) => $w->where('name', 'ilike', "%{$term}%")->orWhere('email', 'ilike', "%{$term}%")))
            ->when(isset($filters['nivel']), fn ($q) => $q->where('verification_level', (int) $filters['nivel']))
            ->when(($filters['rol'] ?? null) === 'admin', fn ($q) => $q->where('is_admin', true))
            ->when(($filters['rol'] ?? null) === 'anfitrion', fn ($q) => $q->has('hostProfile'))
            ->when(($filters['rol'] ?? null) === 'suspendida', fn ($q) => $q->whereNotNull('suspended_at'))
            ->latest()
            ->paginate(30)
            ->withQueryString();

        return view('admin.users.index', ['users' => $users, 'filters' => $filters, 'levels' => VerificationLevel::cases()]);
    }

    public function show(User $user): View
    {
        $user->load(['avatar', 'hostProfile.plan', 'verifications' => fn ($q) => $q->latest(), 'verifications.reviewer', 'invitedBy']);

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
