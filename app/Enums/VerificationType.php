<?php

namespace App\Enums;

enum VerificationType: string
{
    case Email = 'email';
    case Phone = 'phone';
    case Document = 'document';   // DNI, pasaporte u otro documento nacional.
    case Liveness = 'liveness';   // Selfie con prueba de vida.
    case Address = 'address';     // Comprobante de domicilio.
    case Interview = 'interview'; // Videollamada o visita, a pedido de un administrador.

    /** Nivel que aporta cada tipo una vez aprobado. */
    public function level(): VerificationLevel
    {
        return match ($this) {
            self::Email, self::Phone => VerificationLevel::Contact,
            self::Document, self::Liveness => VerificationLevel::Document,
            self::Address, self::Interview => VerificationLevel::Residence,
        };
    }
}
