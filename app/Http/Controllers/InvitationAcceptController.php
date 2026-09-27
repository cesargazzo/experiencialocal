<?php

namespace App\Http\Controllers;

use App\Models\Invitation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class InvitationAcceptController extends Controller
{
    public function __invoke(Request $request, string $token): RedirectResponse
    {
        if ($request->user()) {
            return redirect()->route('home')->with('status', 'Ya tenés una cuenta en Tinku.');
        }

        $invitation = Invitation::findUsableByToken($token);

        if (! $invitation) {
            return redirect()->route('register')->with('status', 'Esa invitación ya se usó o venció. Igual podés crear tu cuenta.');
        }

        $request->session()->put('invitation_token', $token);

        return redirect()->route('register');
    }
}
