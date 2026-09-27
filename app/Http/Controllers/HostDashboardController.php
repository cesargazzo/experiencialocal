<?php

namespace App\Http\Controllers;

use App\Enums\BookingStatus;
use App\Enums\VerificationLevel;
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

        return view('host.dashboard', [
            'profile' => $profile,
            'experiences' => $experiences,
            'canCreateAnother' => $profile->canPublishAnother(),
            'residenceValidated' => $request->user()->hasVerificationLevel(VerificationLevel::Residence),
        ]);
    }
}
