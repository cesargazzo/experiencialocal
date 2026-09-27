<?php

namespace App\Http\Controllers;

use App\Enums\ExperienceStatus;
use App\Models\Experience;
use Illuminate\View\View;

class ExperienceController extends Controller
{
    public function show(Experience $experience): View
    {
        $user = auth()->user();
        $isOwner = $user && $experience->host->user_id === $user->id;

        abort_unless($experience->status === ExperienceStatus::Published || $isOwner || $user?->isAdmin(), 404);

        $experience->load(['host.user', 'category', 'province', 'upcomingDates', 'reviews.user']);

        return view('experiences.show', ['experience' => $experience]);
    }
}
