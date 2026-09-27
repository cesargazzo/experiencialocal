<?php

namespace App\Enums;

/**
 * Opciones de comida que el anfitrión puede garantizar en una experiencia.
 * Sin TACC y sin gluten se separan a propósito: para una persona celíaca la
 * diferencia es la contaminación cruzada.
 */
enum DietaryOption: string
{
    case Vegan = 'vegan';
    case Vegetarian = 'vegetarian';
    case SinTacc = 'sin_tacc';
    case GlutenFree = 'gluten_free';
    case LactoseFree = 'lactose_free';
    case Kosher = 'kosher';
    case Halal = 'halal';

    public function label(): string
    {
        return match ($this) {
            self::Vegan => 'Apto vegano',
            self::Vegetarian => 'Apto vegetariano',
            self::SinTacc => 'Sin TACC',
            self::GlutenFree => 'Opción sin gluten',
            self::LactoseFree => 'Sin lactosa',
            self::Kosher => 'Kosher',
            self::Halal => 'Halal',
        };
    }

    public function hint(): string
    {
        return match ($this) {
            self::Vegan => 'Sin ningún ingrediente de origen animal.',
            self::Vegetarian => 'Sin carne ni pescado.',
            self::SinTacc => 'Apto celíacos: se cocina aparte, sin contaminación cruzada.',
            self::GlutenFree => 'Hay platos sin gluten, pero la cocina no es libre de TACC.',
            self::LactoseFree => 'Sin leche ni derivados, o con opción deslactosada.',
            self::Kosher => 'Preparado según las normas kosher.',
            self::Halal => 'Preparado según las normas halal.',
        };
    }
}
