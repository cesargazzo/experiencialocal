<?php

namespace App\Http\Controllers\Account;

use App\Enums\BookingStatus;
use App\Exceptions\BookingException;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Services\BookingService;
use App\Support\CalendarInvite;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

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

    /** La reserva como .ics, para sumarla al calendario del celular o de la compu. */
    public function calendar(Request $request, Booking $booking): Response
    {
        abort_unless($booking->user_id === $request->user()->id, 404);
        $booking->load(['date', 'experience.province']);

        return response(CalendarInvite::forBooking($booking), 200, [
            'Content-Type' => 'text/calendar; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="tinku-'.$booking->code.'.ics"',
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

        return back()->with('status', __('Cancelaste tu reserva. Le avisamos al anfitrión.'));
    }
}
