<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ExperienceStatus;
use App\Http\Controllers\Controller;
use App\Models\Experience;
use App\Services\ExperienceModeration;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ExperienceController extends Controller
{
    public function index(): View
    {
        $inReview = Experience::query()
            ->with(['host.user', 'category', 'province', 'cover'])
            ->where('status', ExperienceStatus::InReview)
            ->oldest()
            ->get();

        return view('admin.experiences', [
            'pending' => $inReview->whereNull('approved_at'),
            'awaitingHost' => $inReview->whereNotNull('approved_at'),
        ]);
    }

    public function approve(Request $request, Experience $experience, ExperienceModeration $moderation): RedirectResponse
    {
        abort_unless($experience->status === ExperienceStatus::InReview, 409, 'La experiencia ya no está en revisión.');

        $moderation->approve($experience, $request->user());

        return back()->with('status', $experience->status === ExperienceStatus::Published
            ? "Publicamos {$experience->title}."
            : "Aprobada. {$experience->title} se publica cuando el anfitrión valide su domicilio.");
    }

    public function reject(Request $request, Experience $experience, ExperienceModeration $moderation): RedirectResponse
    {
        abort_unless($experience->status === ExperienceStatus::InReview, 409, 'La experiencia ya no está en revisión.');

        $data = $request->validate(['reason' => ['required', 'string', 'max:500']], ['reason.required' => 'Escribí el motivo: se lo mandamos al anfitrión.']);
        $moderation->reject($experience, $data['reason'], $request->user());

        return back()->with('status', "Devolvimos {$experience->title} al anfitrión con el motivo.");
    }
}
