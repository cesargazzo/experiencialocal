<?php

namespace App\Enums;

enum BookingStatus: string
{
    case Requested = 'requested';   // El participante pidió lugar. Todavía no se cobra.
    case Confirmed = 'confirmed';   // El anfitrión confirmó. Se captura el pago.
    case Declined = 'declined';     // El anfitrión rechazó.
    case Cancelled = 'cancelled';   // Canceló el participante o el anfitrión.
    case Completed = 'completed';   // La experiencia ocurrió. Habilita la opinión y la liquidación.
    case Refunded = 'refunded';

    public function label(): string
    {
        return match ($this) {
            self::Requested => __('Solicitada'),
            self::Confirmed => __('Confirmada'),
            self::Declined => __('Rechazada'),
            self::Cancelled => __('Cancelada'),
            self::Completed => __('Realizada'),
            self::Refunded => __('Reembolsada'),
        };
    }

    public function occupiesSeats(): bool
    {
        return in_array($this, [self::Requested, self::Confirmed, self::Completed], true);
    }
}
