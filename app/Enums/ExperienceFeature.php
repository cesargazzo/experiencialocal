<?php

namespace App\Enums;

/** Datos prácticos que el anfitrión marca y que ayudan a decidir. */
enum ExperienceFeature: string
{
    case KidFriendly = 'kids';
    case PetFriendly = 'pets';
    case WheelchairAccessible = 'accessible';
    case TransportIncluded = 'transport';

    public function label(): string
    {
        return match ($this) {
            self::KidFriendly => 'Apta para chicos',
            self::PetFriendly => 'Se aceptan mascotas',
            self::WheelchairAccessible => 'Accesible en silla de ruedas',
            self::TransportIncluded => 'Incluye traslado',
        };
    }

    /** Cómo lo dice la persona que lo necesita. */
    public function needLabel(): string
    {
        return match ($this) {
            self::KidFriendly => 'Voy con chicos',
            self::PetFriendly => 'Viajo con mi mascota',
            self::WheelchairAccessible => 'Necesito accesibilidad para silla de ruedas',
            self::TransportIncluded => 'Necesito traslado incluido',
        };
    }
}
