<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Enums\VerificationLevel;
use App\Models\Booking;
use App\Models\ExperienceDate;
use App\Models\Review;
use App\Models\User;
use App\Notifications\ReviewRequestNotification;
use App\Services\BookingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ReviewsTest extends TestCase
{
    use RefreshDatabase;

    private function confirmedBooking(): Booking
    {
        $date = ExperienceDate::factory()->create(['starts_at' => now()->addDay(), 'ends_at' => now()->addDay()->addHours(3), 'capacity' => 8]);
        $booking = app(BookingService::class)->request(User::factory()->create(['first_name' => 'Lucía', 'last_name' => 'Paz']), $date, 2);
        $booking->forceFill(['status' => BookingStatus::Confirmed, 'confirmed_at' => now()])->save();

        return $booking;
    }

    #[Test]
    public function after_the_experience_the_booking_is_completed_and_the_guest_is_asked_for_a_review(): void
    {
        Notification::fake();
        $booking = $this->confirmedBooking();

        $this->artisan('tinku:complete-bookings')->assertSuccessful();
        $this->assertSame(BookingStatus::Confirmed, $booking->fresh()->status, 'Todavía no pasó.');

        $this->travel(2)->days();
        $this->artisan('tinku:complete-bookings')->expectsOutputToContain('Reservas realizadas: 1.')->assertSuccessful();

        $this->assertSame(BookingStatus::Completed, $booking->fresh()->status);
        Notification::assertSentTo($booking->user, ReviewRequestNotification::class, fn ($notification) => ! $notification->forHost);
    }

    #[Test]
    public function only_the_person_who_went_can_review_once_and_it_shows_on_the_experience(): void
    {
        Notification::fake();
        $booking = $this->confirmedBooking();
        $guest = $booking->user;

        $this->actingAs($guest)->post(route('cuenta.reservas.opinion', $booking), ['rating' => 5, 'body' => 'Una tarde hermosa, volvería mil veces.'])
            ->assertSessionHasErrors('review');

        $booking->forceFill(['status' => BookingStatus::Completed, 'completed_at' => now()])->save();
        $this->actingAs($guest)->get(route('cuenta.reservas'))->assertSee('¿Cómo te fue?');
        $this->actingAs(User::factory()->create())->post(route('cuenta.reservas.opinion', $booking), ['rating' => 1, 'body' => 'No fui pero opino igual, muy mal.'])->assertNotFound();
        $this->actingAs($guest)->post(route('cuenta.reservas.opinion', $booking), ['rating' => 5, 'body' => 'corto'])->assertSessionHasErrors('body');

        $this->actingAs($guest)->post(route('cuenta.reservas.opinion', $booking), ['rating' => 5, 'body' => 'Una tarde hermosa, volvería mil veces.'])->assertSessionHasNoErrors();
        $this->actingAs($guest)->post(route('cuenta.reservas.opinion', $booking), ['rating' => 1, 'body' => 'Cambio de opinión, ahora quiero otra.'])->assertSessionHasErrors('review');

        $experience = $booking->experience->fresh();
        $this->assertSame(1, $experience->reviews_count);
        $this->assertEquals(5, $experience->rating_avg);
        Notification::assertSentTo($experience->host->user, ReviewRequestNotification::class, fn ($notification) => $notification->forHost);
        $this->actingAs(User::factory()->level(VerificationLevel::Contact)->create())
            ->get(route('experiencias.show', $experience))->assertSee('Una tarde hermosa, volvería mil veces.')->assertSee('Lucía')->assertDontSee('Lucía Paz');
    }

    #[Test]
    public function the_host_replies_once_in_public(): void
    {
        $booking = $this->confirmedBooking();
        $booking->forceFill(['status' => BookingStatus::Completed, 'completed_at' => now()])->save();
        $review = Review::create(['booking_id' => $booking->id, 'experience_id' => $booking->experience_id, 'user_id' => $booking->user_id, 'rating' => 4, 'body' => 'Muy lindo todo, faltó un poco de tiempo.', 'published_at' => now()]);
        $host = $booking->experience->host->user;

        $this->actingAs($booking->user)->post(route('anfitrion.opiniones.responder', $review), ['host_reply' => 'No soy el anfitrión'])->assertNotFound();
        $this->actingAs($host)->get(route('anfitrion.panel'))->assertSee('Muy lindo todo');
        $this->actingAs($host)->post(route('anfitrion.opiniones.responder', $review), ['host_reply' => 'Gracias, la próxima sumamos media hora.'])->assertSessionHasNoErrors();
        $this->actingAs($host)->post(route('anfitrion.opiniones.responder', $review), ['host_reply' => 'Otra respuesta más'])->assertSessionHasErrors('reply');

        $this->get(route('experiencias.show', $booking->experience))->assertSee('Gracias, la próxima sumamos media hora.');
    }
}
