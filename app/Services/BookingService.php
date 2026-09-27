<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Enums\ExperienceStatus;
use App\Enums\VerificationLevel;
use App\Exceptions\BookingException;
use App\Models\Booking;
use App\Models\ExperienceDate;
use App\Models\User;
use App\Notifications\BookingUpdatedNotification;
use Illuminate\Support\Facades\DB;

class BookingService
{
    /** Tarifa de servicio que paga el participante sobre el subtotal. */
    public const SERVICE_FEE_RATE = 0.05;

    /**
     * Crea una solicitud de reserva. Bloquea la fila de la fecha para que dos
     * personas no puedan tomar los mismos lugares al mismo tiempo.
     */
    public function request(User $user, ExperienceDate $date, int $guests, ?string $note = null): Booking
    {
        if ($user->isSuspended()) {
            throw new BookingException('Tu cuenta está suspendida.');
        }
        if (! $user->hasVerificationLevel(VerificationLevel::Document)) {
            throw new BookingException('Para reservar necesitás validar tu documento de identidad.');
        }
        if ($guests < 1) {
            throw new BookingException('Indicá cuántas personas van.');
        }

        $booking = DB::transaction(function () use ($user, $date, $guests, $note) {
            /** @var ExperienceDate $locked */
            $locked = ExperienceDate::query()->whereKey($date->getKey())->lockForUpdate()->firstOrFail();
            $experience = $locked->experience()->with('host.plan')->firstOrFail();

            if ($experience->status !== ExperienceStatus::Published) {
                throw new BookingException('Esta experiencia no está recibiendo reservas.');
            }
            if (! $locked->isOpen()) {
                throw new BookingException('Esta fecha ya no está disponible.');
            }
            if ($experience->host->user_id === $user->getKey()) {
                throw new BookingException('No podés reservar tu propia experiencia.');
            }
            if ($locked->seatsLeft() < $guests) {
                throw new BookingException(sprintf('Quedan %d lugares en esta fecha.', $locked->seatsLeft()));
            }

            $unit = (float) $experience->price;
            $subtotal = round($unit * $guests, 2);
            $fee = round($subtotal * self::SERVICE_FEE_RATE, 2);
            $rate = (float) $experience->host->plan->commission_rate;
            $commission = round($subtotal * $rate, 2);

            $booking = Booking::create([
                'experience_date_id' => $locked->getKey(),
                'experience_id' => $experience->getKey(),
                'user_id' => $user->getKey(),
                'guests' => $guests,
                'unit_price' => $unit,
                'subtotal' => $subtotal,
                'service_fee_rate' => self::SERVICE_FEE_RATE,
                'service_fee' => $fee,
                'total' => round($subtotal + $fee, 2),
                'commission_rate' => $rate,
                'commission_amount' => $commission,
                'host_payout' => round($subtotal - $commission, 2),
                'currency' => $experience->currency,
                'status' => BookingStatus::Requested,
                'guest_note' => $note,
                'dietary_needs' => $user->dietary_needs?->map->value->all() ?: null,
                'food_allergies' => $user->food_allergies,
            ]);

            $locked->increment('booked_count', $guests);

            return $booking;
        });

        $booking->experience->host->user->notify(new BookingUpdatedNotification($booking, BookingUpdatedNotification::REQUESTED));

        return $booking;
    }

    /** El anfitrión confirma. Acá se captura el pago autorizado. */
    public function confirm(Booking $booking, User $host): Booking
    {
        $this->assertHostOwns($booking, $host);
        $this->assertStatus($booking, BookingStatus::Requested);

        $booking->forceFill(['status' => BookingStatus::Confirmed, 'confirmed_at' => now()])->save();
        $booking->user->notify(new BookingUpdatedNotification($booking, BookingUpdatedNotification::CONFIRMED));

        return $booking;
    }

    public function decline(Booking $booking, User $host): Booking
    {
        $this->assertHostOwns($booking, $host);
        $this->assertStatus($booking, BookingStatus::Requested);

        $this->release($booking, BookingStatus::Declined, 'declined_at');
        $booking->user->notify(new BookingUpdatedNotification($booking, BookingUpdatedNotification::DECLINED));

        return $booking;
    }

    /** Cancela el participante o el anfitrión. Libera los lugares. */
    public function cancel(Booking $booking, User $actor): Booking
    {
        $isGuest = $booking->user_id === $actor->getKey();
        $isHost = $booking->experience->host->user_id === $actor->getKey();
        if (! $isGuest && ! $isHost && ! $actor->isAdmin()) {
            throw new BookingException('No podés cancelar esta reserva.');
        }
        if (! in_array($booking->status, [BookingStatus::Requested, BookingStatus::Confirmed], true)) {
            throw new BookingException('Esta reserva ya no se puede cancelar.');
        }

        $this->release($booking, BookingStatus::Cancelled, 'cancelled_at');

        // Se avisa a la otra parte.
        $isGuest
            ? $booking->experience->host->user->notify(new BookingUpdatedNotification($booking, BookingUpdatedNotification::CANCELLED_BY_GUEST))
            : $booking->user->notify(new BookingUpdatedNotification($booking, BookingUpdatedNotification::CANCELLED_BY_HOST));

        return $booking;
    }

    public function complete(Booking $booking): Booking
    {
        $this->assertStatus($booking, BookingStatus::Confirmed);
        $booking->forceFill(['status' => BookingStatus::Completed, 'completed_at' => now()])->save();

        return $booking;
    }

    private function release(Booking $booking, BookingStatus $status, string $timestampField): Booking
    {
        return DB::transaction(function () use ($booking, $status, $timestampField) {
            $date = ExperienceDate::query()->whereKey($booking->experience_date_id)->lockForUpdate()->firstOrFail();
            $date->decrement('booked_count', min($booking->guests, $date->booked_count));
            $booking->forceFill(['status' => $status, $timestampField => now()])->save();

            return $booking;
        });
    }

    private function assertHostOwns(Booking $booking, User $host): void
    {
        if ($booking->experience->host->user_id !== $host->getKey() && ! $host->isAdmin()) {
            throw new BookingException('Esta reserva no es de una experiencia tuya.');
        }
    }

    private function assertStatus(Booking $booking, BookingStatus $expected): void
    {
        if ($booking->status !== $expected) {
            throw new BookingException(sprintf('La reserva está %s.', mb_strtolower($booking->status->label())));
        }
    }
}
