<?php

namespace App\Http\Controllers;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Conversation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Cómo le va a cada experiencia: visitas, consultas, pedidos de reserva y
 * cuántas visitas terminan en reserva, en un período a elegir.
 */
class HostStatsController extends Controller
{
    /** @var list<int> */
    public const PERIODS = [7, 30, 90];

    public function __invoke(Request $request): View|RedirectResponse
    {
        $profile = $request->user()->hostProfile;
        if (! $profile) {
            return redirect()->route('anfitrion.registro');
        }

        $days = in_array((int) $request->query('dias'), self::PERIODS, true) ? (int) $request->query('dias') : 30;
        $since = now()->subDays($days);
        $experiences = $profile->experiences()->orderBy('title')->get(['id', 'title', 'slug', 'status', 'rating_avg', 'reviews_count']);
        $ids = $experiences->pluck('id');

        $views = DB::table('experience_daily_views')->whereIn('experience_id', $ids)
            ->where('day', '>=', $since->copy()->timezone(config('tinku.timezone'))->toDateString())
            ->selectRaw('experience_id, sum(views) as total')->groupBy('experience_id')->pluck('total', 'experience_id');
        $questions = Conversation::query()->whereIn('experience_id', $ids)->where('created_at', '>=', $since)
            ->selectRaw('experience_id, count(*) as total')->groupBy('experience_id')->pluck('total', 'experience_id');
        $requests = Booking::query()->whereIn('experience_id', $ids)->where('created_at', '>=', $since)
            ->selectRaw('experience_id, count(*) as total')->groupBy('experience_id')->pluck('total', 'experience_id');
        $confirmed = Booking::query()->whereIn('experience_id', $ids)->where('created_at', '>=', $since)
            ->whereIn('status', [BookingStatus::Confirmed, BookingStatus::Completed])
            ->selectRaw('experience_id, count(*) as total')->groupBy('experience_id')->pluck('total', 'experience_id');
        $favorites = DB::table('favorites')->whereIn('experience_id', $ids)->selectRaw('experience_id, count(*) as total')->groupBy('experience_id')->pluck('total', 'experience_id');

        $rows = $experiences->map(fn ($experience) => [
            'experience' => $experience,
            'views' => (int) ($views[$experience->id] ?? 0),
            'questions' => (int) ($questions[$experience->id] ?? 0),
            'requests' => (int) ($requests[$experience->id] ?? 0),
            'confirmed' => (int) ($confirmed[$experience->id] ?? 0),
            'favorites' => (int) ($favorites[$experience->id] ?? 0),
        ]);

        $totals = [
            'views' => $rows->sum('views'), 'questions' => $rows->sum('questions'), 'requests' => $rows->sum('requests'),
            'confirmed' => $rows->sum('confirmed'), 'favorites' => $rows->sum('favorites'),
        ];

        return view('host.stats', ['rows' => $rows, 'totals' => $totals, 'days' => $days, 'periods' => self::PERIODS]);
    }

    /** Qué porcentaje de las visitas terminó en un pedido de reserva. */
    public static function conversion(int $views, int $requests): string
    {
        return $views > 0 ? number_format($requests / $views * 100, 1, ',', '.').'%' : '—';
    }
}
