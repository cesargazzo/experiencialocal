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
            'profile' => $profile,
            'experiences' => $experiences,
            'canCreateAnother' => $profile->canPublishAnother(),
            'residenceValidated' => $request->user()->hasVerificationLevel(VerificationLevel::Residence),
        ]);
    }
}
