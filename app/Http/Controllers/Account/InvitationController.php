<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\Invitation;
use App\Models\User;
use App\Notifications\InvitationNotification;
use App\Services\SecurityLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\View\View;

class InvitationController extends Controller
{
    public function index(Request $request): View
    {
        return view('account.invitations', [
            'invitations' => $request->user()->invitations()->with('acceptedBy')->latest()->limit(50)->get(),
            'remainingToday' => $this->remainingToday($request->user()),
        ]);
    }

    /** Invitación por email: le llega un mail con el enlace. */
    public function sendEmail(Request $request, SecurityLog $securityLog): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255'],
        ]);

        if ($this->remainingToday($request->user()) <= 0) {
            return back()->withErrors(['email' => 'Llegaste al límite de invitaciones por hoy. Probá mañana.'])->withInput();
        }

        $invitation = Invitation::issue($request->user(), ['channel' => 'email', ...$data]);

        // Si ya tiene cuenta no se manda nada, pero la respuesta es la misma: no revela quién está registrado.
        $alreadyRegistered = User::query()->where('email', $invitation->email)->exists();
        if (! $alreadyRegistered) {
            Notification::route('mail', [$invitation->email => $invitation->name])
                ->notify(new InvitationNotification($invitation, $invitation->url()));
        }

        $securityLog->record('invitation.sent', $request->user(), ['invitation_id' => $invitation->id, 'already_registered' => $alreadyRegistered], $invitation->email);

        return back()->with('status', "Listo, le mandamos la invitación a {$data['name']}.");
    }

    /** Enlace para compartir por WhatsApp u otro medio. Se muestra una sola vez. */
    public function createLink(Request $request, SecurityLog $securityLog): RedirectResponse
    {
        $data = $request->validateWithBag('link', ['name' => ['nullable', 'string', 'max:120']]);

        if ($this->remainingToday($request->user()) <= 0) {
            return back()->withErrors(['name' => 'Llegaste al límite de invitaciones por hoy. Probá mañana.'], 'link');
        }

        $invitation = Invitation::issue($request->user(), ['channel' => 'link', 'name' => $data['name'] ?? null]);
        $securityLog->record('invitation.link_created', $request->user(), ['invitation_id' => $invitation->id]);

        return back()->with('invitation_link', [
            'url' => $invitation->url(),
            'name' => $invitation->name,
            'expires' => $invitation->expires_at->timezone(config('tinku.timezone'))->translatedFormat('j \d\e F'),
        ]);
    }

    private function remainingToday(User $user): int
    {
        $sentToday = $user->invitations()->where('created_at', '>=', now()->subDay())->count();

        return max(0, (int) config('tinku.invitations.daily_limit') - $sentToday);
    }
}
