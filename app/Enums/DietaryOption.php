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

    /** Cómo se nombra cuando es una necesidad de la persona y no una oferta del anfitrión. */
    public function needLabel(): string
    {
        return match ($this) {
            self::Vegan => 'Alimentación vegana',
            self::Vegetarian => 'Alimentación vegetariana',
            self::SinTacc => 'Celiaquía (sin TACC)',
            self::GlutenFree => 'Sin gluten',
            self::LactoseFree => 'Sin lactosa',
            self::Kosher => 'Kosher',
            self::Halal => 'Halal',
        };
    }

    /**
     * Si lo que ofrece la experiencia alcanza para esta necesidad: vegano cubre
     * vegetariano y sin TACC cubre sin gluten, pero no al revés.
     *
     * @param  iterable<self>  $offered
     */
    public function isCoveredBy(iterable $offered): bool
    {
        $accepted = match ($this) {
            self::Vegetarian => [self::Vegetarian, self::Vegan],
            self::GlutenFree => [self::GlutenFree, self::SinTacc],
            default => [$this],
        };

        foreach ($offered as $option) {
            if (in_array($option, $accepted, true)) {
                return true;
            }
        }

        return false;
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
