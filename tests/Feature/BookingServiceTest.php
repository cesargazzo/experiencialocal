<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Enums\VerificationLevel;
use App\Exceptions\BookingException;
use App\Models\ExperienceDate;
use App\Models\User;
use App\Services\BookingService;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class BookingServiceTest extends TestCase
{
    use RefreshDatabase;

    private BookingService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(BookingService::class);
    }

    #[Test]
    public function it_freezes_the_full_breakdown_on_the_booking(): void
    {
        $date = ExperienceDate::factory()->create(['capacity' => 8]);
        $guest = User::factory()->create();

        $booking = $this->service->request($guest, $date, 3, 'Sin gluten, por favor');

        // 40.000 × 3 = 120.000; tarifa 5 % = 6.000; comisión 18 % = 21.600; anfitrión recibe 98.400.
        $this->assertSame('120000.00', $booking->subtotal);
        $this->assertSame('6000.00', $booking->service_fee);
        $this->assertSame('126000.00', $booking->total);
        $this->assertSame('21600.00', $booking->commission_amount);
        $this->assertSame('98400.00', $booking->host_payout);
        $this->assertSame(BookingStatus::Requested, $booking->status);
        $this->assertStringStartsWith('TK-', $booking->code);
        $this->assertSame(3, $date->fresh()->booked_count);
    }

    #[Test]
    public function it_never_oversells_a_date(): void
    {
        $date = ExperienceDate::factory()->create(['capacity' => 6]);
        $this->service->request(User::factory()->create(), $date, 4);

        $this->expectException(BookingException::class);
        $this->expectExceptionMessage('Quedan 2 lugares');

        $this->service->request(User::factory()->create(), $date, 3);
    }

    #[Test]
    public function the_database_itself_rejects_overbooking(): void
    {
        $date = ExperienceDate::factory()->create(['capacity' => 2, 'booked_count' => 2]);

        $this->expectException(QueryException::class);
        $date->increment('booked_count');
    }

    #[Test]
    public function it_requires_a_validated_document_to_book(): void
    {
        $date = ExperienceDate::factory()->create();
        $guest = User::factory()->level(VerificationLevel::Contact)->create();

        $this->expectException(BookingException::class);
        $this->expectExceptionMessage('validar tu documento');

        $this->service->request($guest, $date, 1);
    }

    #[Test]
    public function a_host_cannot_book_their_own_experience(): void
    {
        $date = ExperienceDate::factory()->create();
        $host = $date->experience->host->user;

        $this->expectException(BookingException::class);
        $this->service->request($host, $date, 1);
    }

    #[Test]
    public function declining_or_cancelling_releases_the_seats(): void
    {
        $date = ExperienceDate::factory()->create(['capacity' => 6]);
        $guest = User::factory()->create();
        $host = $date->experience->host->user;

        $booking = $this->service->request($guest, $date, 4);
        $this->assertSame(4, $date->fresh()->booked_count);

        $this->service->decline($booking, $host);
        $this->assertSame(BookingStatus::Declined, $booking->fresh()->status);
        $this->assertSame(0, $date->fresh()->booked_count);

        $second = $this->service->request($guest, $date, 2);
        $this->service->confirm($second, $host);
        $this->service->cancel($second, $guest);
        $this->assertSame(BookingStatus::Cancelled, $second->fresh()->status);
        $this->assertSame(0, $date->fresh()->booked_count);
    }

    #[Test]
    public function only_the_host_or_an_admin_can_confirm(): void
    {
        $date = ExperienceDate::factory()->create();
        $booking = $this->service->request(User::factory()->create(), $date, 1);

        $this->expectException(BookingException::class);
        $this->service->confirm($booking, User::factory()->create());
    }
}
