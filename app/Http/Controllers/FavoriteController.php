<?php

namespace App\Http\Controllers;

use App\Models\Experience;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Experiencias guardadas para más adelante. */
class FavoriteController extends Controller
{
    public function index(Request $request): View
    {
        return view('account.favorites', [
            'experiences' => $request->user()->favoriteExperiences()->published()
                ->with(['host.user.avatar', 'host.plan', 'category', 'province', 'cover'])
                ->orderByPivot('created_at', 'desc')->get(),
        ]);
    }

    /** Guarda o saca. Responde JSON al botón del corazón; sin JavaScript, vuelve a la página. */
    public function toggle(Request $request, Experience $experience): JsonResponse|RedirectResponse
    {
        abort_unless($experience->isPublished(), 404);

        $favorites = $request->user()->favoriteExperiences();
        $saved = ! $favorites->whereKey($experience->getKey())->exists();
        $saved
            ? $favorites->attach($experience->getKey(), ['created_at' => now()])
            : $favorites->detach($experience->getKey());

        return $request->expectsJson()
            ? response()->json(['favorite' => $saved])
            : back()->with('status', $saved ? 'La guardaste en tus favoritas.' : 'La sacaste de tus favoritas.');
    }
}
