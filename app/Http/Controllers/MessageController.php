<?php

namespace App\Http\Controllers;

use App\Enums\ExperienceStatus;
use App\Enums\VerificationLevel;
use App\Models\Conversation;
use App\Models\Experience;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class MessageController extends Controller
{
    /** Bandeja: las conversaciones como viajero y como anfitrión. */
    public function index(Request $request): View
    {
        $user = $request->user();

        return view('messages.index', [
            'user' => $user,
            'conversations' => Conversation::query()->for($user)
                ->whereNotNull('last_message_at')
                ->with(['experience', 'guest.avatar', 'hostUser.avatar', 'latestMessage'])
                ->latest('last_message_at')
                ->get(),
        ]);
    }

    /** "Preguntale a…": abre (o retoma) la conversación con el anfitrión de la experiencia. */
    public function start(Request $request, Experience $experience): RedirectResponse
    {
        $user = $request->user();
        $experience->load('host');

        if ($experience->host->user_id === $user->id) {
            return redirect()->route('mensajes')->with('status', 'Es tu experiencia: acá ves lo que te preguntan.');
        }
        abort_unless($experience->status === ExperienceStatus::Published, 404);
        if (! $user->hasVerificationLevel(VerificationLevel::Contact)) {
            return redirect()->route('verificacion')->with('status', 'Para escribirle a un anfitrión, confirmá primero tu email.');
        }

        $conversation = Conversation::firstOrCreate(
            ['experience_id' => $experience->id, 'guest_id' => $user->id],
            ['host_user_id' => $experience->host->user_id],
        );

        return redirect()->route('mensajes.show', $conversation);
    }

    /** Para quien no inició sesión: después de ingresar vuelve a la experiencia, al botón para escribir. */
    public function prompt(Experience $experience): RedirectResponse
    {
        return redirect()->to(route('experiencias.show', $experience).'#anfitrion');
    }

    public function show(Request $request, Conversation $conversation): View
    {
        Gate::authorize('view', $conversation);

        return view('messages.show', ['conversation' => $conversation->load(['experience', 'guest', 'hostUser'])]);
    }
}
