<?php

namespace App\Http\Controllers\Admin;

use App\Enums\TeamPermission;
use App\Enums\TeamRole;
use App\Enums\VerificationLevel;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\SecurityLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** Quién es parte del equipo y con qué rol. Solo lo maneja la administración total. */
class TeamController extends Controller
{
    public function index(): View
    {
        return view('admin.team', [
            'members' => User::query()->with('avatar')->whereNotNull('team_role')->orderBy('team_role')->orderBy('name')->get(),
            'roles' => TeamRole::cases(),
            'permissions' => TeamPermission::cases(),
        ]);
    }

    public function update(Request $request, User $user, SecurityLog $securityLog): RedirectResponse
    {
        $data = $request->validate(['team_role' => ['nullable', Rule::enum(TeamRole::class)]]);
        $role = TeamRole::tryFrom((string) ($data['team_role'] ?? ''));

        // Nadie se cambia su propio rol: así siempre queda al menos una administración total.
        if ($user->is($request->user())) {
            return back()->withErrors(['team_role' => 'No podés cambiar tu propio rol: pedíselo a otra persona con administración total.']);
        }
        if ($role !== null && ! $user->hasVerificationLevel(VerificationLevel::Document)) {
            return back()->withErrors(['team_role' => 'Para sumarse al equipo, la persona tiene que tener la identidad validada (nivel 2).']);
        }
        if ($role !== null && $user->isSuspended()) {
            return back()->withErrors(['team_role' => 'La cuenta está suspendida.']);
        }

        $previous = $user->team_role;
        $user->forceFill(['team_role' => $role])->save();
        $securityLog->record('team.role_changed', $user, [
            'antes' => $previous?->label() ?? 'sin rol',
            'después' => $role?->label() ?? 'sin rol',
            'por' => $request->user()->email,
        ], $user->email, 'warning');

        return back()->with('status', $role ? "{$user->name} ahora tiene el rol «{$role->label()}»." : "{$user->name} ya no es parte del equipo.");
    }
}
