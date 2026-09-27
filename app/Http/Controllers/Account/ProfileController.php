<?php

namespace App\Http\Controllers\Account;

use App\Enums\VerificationLevel;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function edit(Request $request): View
    {
        return view('account.profile', [
            'user' => $request->user()->load('avatar'),
            'nameLocked' => $this->nameIsLocked($request),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        abort_if($this->nameIsLocked($request), 403, 'Tu nombre ya quedó validado con tu documento.');

        $data = $request->validate(['name' => ['required', 'string', 'max:120']]);
        $request->user()->update(['name' => $data['name']]);

        return back()->with('status', 'Guardamos tus datos.');
    }

    /** Con el documento validado, el nombre queda fijo: es el que figura en el documento. */
    private function nameIsLocked(Request $request): bool
    {
        return $request->user()->hasVerificationLevel(VerificationLevel::Document);
    }
}
