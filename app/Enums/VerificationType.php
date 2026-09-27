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

    public function label(): string
    {
        return match ($this) {
            self::Email => 'email',
            self::Phone => 'teléfono',
            self::Document => 'documento',
            self::Liveness => 'selfie con prueba de vida',
            self::Address => 'domicilio',
            self::Interview => 'entrevista',
        };
    }

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
