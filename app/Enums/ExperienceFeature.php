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
            self::KidFriendly => __('Apta para chicos'),
            self::PetFriendly => __('Se aceptan mascotas'),
            self::WheelchairAccessible => __('Accesible en silla de ruedas'),
            self::TransportIncluded => __('Incluye traslado'),
        };
    }

    /** Cómo lo dice la persona que lo necesita. */
    public function needLabel(): string
    {
        return match ($this) {
            self::KidFriendly => __('Voy con chicos'),
            self::PetFriendly => __('Viajo con mi mascota'),
            self::WheelchairAccessible => __('Necesito accesibilidad para silla de ruedas'),
            self::TransportIncluded => __('Necesito traslado incluido'),
        };
    }
}
