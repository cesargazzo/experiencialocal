<?php

namespace App\Http\Controllers\Account;

use App\Enums\BookingStatus;
use App\Exceptions\BookingException;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Services\BookingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BookingController extends Controller
{
    /** Próximas reservas (pedidas o confirmadas que todavía no pasaron) e historial. */
    public function index(Request $request): View
    {
        $bookings = $request->user()->bookings()
            ->with(['date', 'experience.host.user', 'experience.province', 'experience.cover', 'review'])
            ->get();

        [$upcoming, $past] = $bookings->partition(fn ($booking) => $booking->date->starts_at->isFuture()
            && in_array($booking->status, [BookingStatus::Requested, BookingStatus::Confirmed], true));

        return view('account.bookings', [
            'upcoming' => $upcoming->sortBy(fn ($booking) => $booking->date->starts_at)->values(),
            'past' => $past->sortByDesc(fn ($booking) => $booking->date->starts_at)->values(),
        ]);
    }

    public function cancel(Request $request, Booking $booking, BookingService $bookings): RedirectResponse
    {
        abort_unless($booking->user_id === $request->user()->id, 404);

        try {
            $bookings->cancel($booking, $request->user());
        } catch (BookingException $e) {
            return back()->withErrors(['booking' => $e->getMessage()]);
        }

        return back()->with('status', 'Cancelaste tu reserva. Le avisamos al anfitrión.');
    }
}
