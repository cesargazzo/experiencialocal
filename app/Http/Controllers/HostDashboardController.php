<?php

namespace App\Http\Controllers;

use App\Enums\BookingStatus;
use App\Enums\VerificationLevel;
use App\Models\Booking;
use App\Models\Review;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HostDashboardController extends Controller
{
    /** Panel del anfitrión: su estado, su plan y todas sus experiencias, publicadas o no. */
    public function index(Request $request): View|RedirectResponse
    {
        $profile = $request->user()->hostProfile;

        if (! $profile) {
            return redirect()->route('anfitrion.registro');
        }

        $profile->load('plan');
        $experiences = $profile->experiences()
            ->with(['cover', 'latestCoverUpload', 'province', 'upcomingDates'])
            ->withCount(['bookings as pending_bookings_count' => fn ($q) => $q->where('status', BookingStatus::Requested)])
            ->latest()
            ->get();

        $bookings = Booking::query()
            ->whereIn('experience_id', $experiences->pluck('id'))
            ->whereIn('status', [BookingStatus::Requested, BookingStatus::Confirmed])
            ->whereHas('date', fn ($q) => $q->where('starts_at', '>', now()))
            ->with(['user.avatar', 'date', 'experience.province'])
            ->get()
            ->sortBy(fn (Booking $booking) => $booking->date->starts_at);

        // Las que ya empezaron y todavía se pueden cerrar: si la persona no vino, el anfitrión lo marca acá.
        $toClose = Booking::query()
            ->whereIn('experience_id', $experiences->pluck('id'))
            ->whereIn('status', [BookingStatus::Confirmed, BookingStatus::Completed])
            ->whereDoesntHave('review')
            ->whereHas('date', fn ($q) => $q->where('starts_at', '<=', now())->where('starts_at', '>=', now()->subHours(config('tinku.no_shows.mark_window_hours'))))
            ->with(['user.avatar', 'date', 'experience.province'])
            ->get()
            ->sortByDesc(fn (Booking $booking) => $booking->date->starts_at)
            ->values();

        $noShows = Booking::query()
            ->whereIn('user_id', $bookings->pluck('user_id'))
            ->where('status', BookingStatus::NoShow)
            ->where('no_show_at', '>=', now()->subMonths(config('tinku.no_shows.months')))
            ->selectRaw('user_id, count(*) as total')->groupBy('user_id')
            ->pluck('total', 'user_id')
            ->map(fn ($total) => (int) $total);

        $reviews = Review::query()
            ->whereIn('experience_id', $experiences->pluck('id'))
            ->whereNotNull('published_at')
            ->with(['user', 'experience'])
            ->latest('published_at')
            ->limit(20)
            ->get();

        return view('host.dashboard', [
            'reviews' => $reviews,
            'pendingBookings' => $bookings->where('status', BookingStatus::Requested)->values(),
            'confirmedBookings' => $bookings->where('status', BookingStatus::Confirmed)->values(),
            'bookingsToClose' => $toClose,
            'noShows' => $noShows,
            'profile' => $profile,
            'experiences' => $experiences,
            'canCreateAnother' => $profile->canPublishAnother(),
            'residenceValidated' => $request->user()->hasVerificationLevel(VerificationLevel::Residence),
        ]);
    }
}
