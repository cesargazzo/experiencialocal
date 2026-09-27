<?php

namespace App\Http\Controllers;

use App\Exceptions\BookingException;
use App\Models\Booking;
use App\Services\BookingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class HostBookingController extends Controller
{
    public function confirm(Request $request, Booking $booking, BookingService $bookings): RedirectResponse
    {
        try {
            $bookings->confirm($booking, $request->user());
        } catch (BookingException $e) {
            return back()->withErrors(['booking' => $e->getMessage()]);
        }

        return back()->with('status', 'Confirmaste la reserva de '.str($booking->user->name)->before(' ').'. Le avisamos.');
    }

    public function decline(Request $request, Booking $booking, BookingService $bookings): RedirectResponse
    {
        try {
            $bookings->decline($booking, $request->user());
        } catch (BookingException $e) {
            return back()->withErrors(['booking' => $e->getMessage()]);
        }

        return back()->with('status', 'Rechazaste la reserva. Le avisamos y no se le cobra nada.');
    }
}
