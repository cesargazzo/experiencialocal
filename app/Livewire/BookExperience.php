<?php

namespace App\Livewire;

use App\Enums\BookingStatus;
use App\Enums\VerificationLevel;
use App\Exceptions\BookingException;
use App\Models\Booking;
use App\Models\Experience;
use App\Models\ExperienceDate;
use App\Services\BookingService;
use Livewire\Attributes\Validate;
use Livewire\Component;

class BookExperience extends Component
{
    public Experience $experience;

    #[Validate('required|integer|exists:experience_dates,id')]
    public ?int $dateId = null;

    #[Validate('required|integer|min:1')]
    public int $guests = 2;

    #[Validate('nullable|string|max:500')]
    public string $note = '';

    public ?Booking $booking = null;

    public function mount(): void
    {
        $this->dateId = $this->experience->upcomingDates->first()?->id;
        $this->guests = min(2, $this->experience->max_guests);
    }

    public function getSelectedDateProperty(): ?ExperienceDate
    {
        return $this->experience->upcomingDates->firstWhere('id', $this->dateId);
    }

    /** La reserva activa que la persona ya tiene para la fecha elegida, si hay. */
    public function getExistingBookingProperty(): ?Booking
    {
        return auth()->check() && $this->dateId
            ? Booking::query()
                ->where('experience_date_id', $this->dateId)
                ->where('user_id', auth()->id())
                ->whereIn('status', [BookingStatus::Requested, BookingStatus::Confirmed])
                ->first()
            : null;
    }

    public function getSubtotalProperty(): float
    {
        return round((float) $this->experience->price * $this->guests, 2);
    }

    public function getServiceFeeProperty(): float
    {
        return round($this->subtotal * BookingService::SERVICE_FEE_RATE, 2);
    }

    public function submit(BookingService $bookings): void
    {
        if (! auth()->check()) {
            session()->put('url.intended', route('experiencias.show', $this->experience));
            $this->redirectRoute('login');

            return;
        }
        if (! auth()->user()->hasVerificationLevel(VerificationLevel::Document)) {
            session()->flash('status', __('Para reservar necesitás validar tu documento de identidad.'));
            $this->redirectRoute('verificacion');

            return;
        }

        $this->validate();

        try {
            $this->booking = $bookings->request(auth()->user(), ExperienceDate::findOrFail($this->dateId), $this->guests, $this->note ?: null);
            $this->experience->load('upcomingDates');
        } catch (BookingException $e) {
            $this->addError('dateId', $e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.book-experience');
    }
}
