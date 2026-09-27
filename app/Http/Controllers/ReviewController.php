<?php

namespace App\Http\Controllers;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Review;
use App\Notifications\ReviewRequestNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    /** Días que tiene quien fue para opinar. */
    public const WINDOW_DAYS = 60;

    /** Opinan solo quienes fueron: la reserva tiene que estar realizada y ser suya. */
    public function store(Request $request, Booking $booking): RedirectResponse
    {
        abort_unless($booking->user_id === $request->user()->id, 404);

        if ($booking->status !== BookingStatus::Completed || $booking->review()->exists()) {
            return back()->withErrors(['review' => 'Esta reserva no se puede opinar.']);
        }
        if ($booking->completed_at?->lt(now()->subDays(self::WINDOW_DAYS))) {
            return back()->withErrors(['review' => 'Pasaron más de '.self::WINDOW_DAYS.' días desde la experiencia.']);
        }

        $data = $request->validate([
            'rating' => ['required', 'integer', 'between:1,5'],
            'body' => ['required', 'string', 'min:20', 'max:1500'],
        ], [
            'rating.required' => 'Elegí de 1 a 5 estrellas.',
            'body.required' => 'Contanos cómo te fue.',
            'body.min' => 'Contanos un poco más (al menos 20 caracteres).',
        ]);

        Review::create([
            'booking_id' => $booking->id,
            'experience_id' => $booking->experience_id,
            'user_id' => $booking->user_id,
            'rating' => $data['rating'],
            'body' => trim($data['body']),
            'published_at' => now(),
        ]);
        $booking->experience->refreshRating();
        $booking->experience->host->user->notify(new ReviewRequestNotification($booking, forHost: true));

        return back()->with('status', 'Gracias por tu opinión. Ya se ve en la experiencia.');
    }

    /** El anfitrión responde una vez, en público, a cada opinión de su experiencia. */
    public function reply(Request $request, Review $review): RedirectResponse
    {
        abort_unless($review->experience->host->user_id === $request->user()->id, 404);

        if ($review->host_reply) {
            return back()->withErrors(['reply' => 'Ya respondiste esta opinión.']);
        }

        $data = $request->validate(['host_reply' => ['required', 'string', 'min:5', 'max:1000']], ['host_reply.required' => 'Escribí tu respuesta.']);
        $review->update(['host_reply' => trim($data['host_reply'])]);

        return back()->with('status', 'Publicamos tu respuesta.');
    }
}
