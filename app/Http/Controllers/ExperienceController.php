<?php

namespace App\Http\Controllers;

use App\Enums\ExperienceStatus;
use App\Enums\TeamPermission;
use App\Models\Experience;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ExperienceController extends Controller
{
    /** Navegadores automáticos que no cuentan como visitas. */
    private const BOT_PATTERN = '/bot|crawl|spider|slurp|preview|facebookexternalhit|whatsapp|curl|wget|python|headless/i';

    public function show(Request $request, Experience $experience): View
    {
        $user = $request->user();
        $isOwner = $user && $experience->host->user_id === $user->id;

        abort_unless($experience->status === ExperienceStatus::Published || $isOwner || $user?->hasTeamPermission(TeamPermission::ModerateExperiences), 404);

        $experience->load(['host.user.avatar', 'category', 'province', 'cover', 'galleryPhotos', 'upcomingDates', 'reviews.user']);

        if ($experience->isPublished() && ! $isOwner && ! $user?->isTeamMember()) {
            $this->countView($request, $experience);
        }

        return view('experiences.show', ['experience' => $experience]);
    }

    /** Una visita por persona (sesión), experiencia y día. Sin datos de quién fue. */
    private function countView(Request $request, Experience $experience): void
    {
        $day = now(config('tinku.timezone'))->toDateString();
        $key = "viewed.{$experience->id}";
        if ($request->session()->get($key) === $day || preg_match(self::BOT_PATTERN, (string) $request->userAgent())) {
            return;
        }

        $request->session()->put($key, $day);
        DB::table('experience_daily_views')->upsert(
            ['experience_id' => $experience->id, 'day' => $day, 'views' => 1],
            ['experience_id', 'day'],
            ['views' => DB::raw('experience_daily_views.views + 1')],
        );
    }
}
