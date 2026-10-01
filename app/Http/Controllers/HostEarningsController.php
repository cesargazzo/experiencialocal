<?php

namespace App\Http\Controllers;

use App\Enums\BookingStatus;
use App\Models\Booking;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Cuánto ganó el anfitrión, cuánto tiene por cobrar y de qué reservas.
 * Todo sale de las reservas: lo cobrado es lo de experiencias realizadas.
 */
class HostEarningsController extends Controller
{
    public function __invoke(Request $request): View|RedirectResponse
    {
        $profile = $request->user()->hostProfile;
        if (! $profile) {
            return redirect()->route('anfitrion.registro');
        }

        $experienceIds = $profile->experiences()->pluck('id');
        $bookings = Booking::query()
            ->whereIn('experience_id', $experienceIds)
            ->whereIn('status', [BookingStatus::Requested, BookingStatus::Confirmed, ...BookingStatus::settled()])
            ->with(['date', 'experience', 'user'])
            ->get()
            ->sortByDesc(fn (Booking $booking) => $booking->date->starts_at)
            ->values();

        $completed = $bookings->whereIn('status', BookingStatus::settled());
        $upcomingConfirmed = $bookings->where('status', BookingStatus::Confirmed)->filter(fn (Booking $b) => $b->date->starts_at->isFuture());
        $requested = $bookings->where('status', BookingStatus::Requested)->filter(fn (Booking $b) => $b->date->starts_at->isFuture());

        $timezone = config('tinku.timezone');
        $months = collect(range(11, 0))->map(function (int $ago) use ($completed, $timezone) {
            $month = CarbonImmutable::now($timezone)->startOfMonth()->subMonths($ago);
            $inMonth = $completed->filter(fn (Booking $b) => $b->date->starts_at->timezone($timezone)->isSameMonth($month));

            return [
                'label' => ucfirst($month->translatedFormat('F Y')),
                'bookings' => $inMonth->count(),
                'guests' => (int) $inMonth->sum('guests'),
                'payout' => (float) $inMonth->sum('host_payout'),
            ];
        })->filter(fn (array $month) => $month['bookings'] > 0)->reverse()->values();

        return view('host.earnings', [
            'profile' => $profile,
            'earned' => (float) $completed->sum('host_payout'),
            'commission' => (float) $completed->sum('commission_amount'),
            'toCollect' => (float) $upcomingConfirmed->sum('host_payout'),
            'requestedAmount' => (float) $requested->sum('host_payout'),
            'requestedCount' => $requested->count(),
            'months' => $months,
            'movements' => $bookings->filter(fn (Booking $b) => $b->status !== BookingStatus::Requested)->take(50),
        ]);
    }
}
