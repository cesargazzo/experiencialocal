<?php

namespace App\Enums;

/**
 * Nivel de verificación de identidad de una cuenta. Es acumulativo y cada
 * acción de la plataforma exige un nivel mínimo.
 */
enum VerificationLevel: int
{
    case None = 0;
    case Contact = 1;     // Email y teléfono confirmados.
    case Document = 2;    // Documento validado más prueba de vida.
    case Residence = 3;   // Nivel 2 más domicilio validado y revisión de un administrador.

    public function label(): string
    {
        return match ($this) {
            self::None => 'Sin verificar',
            self::Contact => 'Email y teléfono',
            self::Document => 'Documento validado',
            self::Residence => 'Identidad y domicilio validados',
        };
    }

    public function atLeast(self $other): bool
    {
        return $this->value >= $other->value;
    }
}
