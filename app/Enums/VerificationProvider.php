<?php

namespace App\Enums;

/**
 * Quién valida. Para documentos argentinos se usa RENAPER; para el resto del
 * mundo un proveedor de KYC internacional. El flujo para el usuario es el mismo.
 */
enum VerificationProvider: string
{
    case Internal = 'internal'; // Códigos de email y teléfono enviados por Tinku.
    case Renaper = 'renaper';
    case Metamap = 'metamap';
    case Sumsub = 'sumsub';
    case Manual = 'manual';     // Revisión de un administrador.

    public static function forDocumentCountry(string $countryCode): self
    {
        return strtoupper($countryCode) === 'AR' ? self::Renaper : self::Metamap;
    }
}
