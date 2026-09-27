<?php

namespace App\Http\Controllers\Account;

use App\Enums\VerificationLevel;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function edit(Request $request): View
    {
        $user = $request->user()->load('avatar');

        return view('account.profile', [
            'user' => $user,
            'nameLocked' => $this->identityIsLocked($user),
            'birthDateLocked' => $this->identityIsLocked($user) && $user->birth_date !== null,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();
        $locked = $this->identityIsLocked($user);
        $birthDateLocked = $locked && $user->birth_date !== null;

        abort_if($locked && $birthDateLocked, 403, 'Tus datos ya quedaron validados con tu documento.');

        $data = $request->validate([
            'name' => $locked ? ['prohibited'] : ['required', 'string', 'max:120'],
            'birth_date' => $birthDateLocked ? ['prohibited'] : ['required', ...User::birthDateRules()],
        ], [
            'birth_date.before_or_equal' => 'Tenés que tener al menos '.config('tinku.min_age').' años para usar Tinku.',
            'birth_date.after' => 'Revisá la fecha de nacimiento.',
        ]);

        $user->update($data);

        return back()->with('status', 'Guardamos tus datos.');
    }

    /** Con el documento validado, nombre y fecha de nacimiento quedan fijos: son los del documento. */
    private function identityIsLocked(User $user): bool
    {
        return $user->hasVerificationLevel(VerificationLevel::Document);
    }
}
