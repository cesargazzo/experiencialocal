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
            self::Vegan => __('Apto vegano'),
            self::Vegetarian => __('Apto vegetariano'),
            self::SinTacc => __('Sin TACC'),
            self::GlutenFree => __('Opción sin gluten'),
            self::LactoseFree => __('Sin lactosa'),
            self::Kosher => __('Kosher'),
            self::Halal => __('Halal'),
        };
    }

    /** Cómo se nombra cuando es una necesidad de la persona y no una oferta del anfitrión. */
    public function needLabel(): string
    {
        return match ($this) {
            self::Vegan => __('Alimentación vegana'),
            self::Vegetarian => __('Alimentación vegetariana'),
            self::SinTacc => __('Celiaquía (sin TACC)'),
            self::GlutenFree => __('Sin gluten'),
            self::LactoseFree => __('Sin lactosa'),
            self::Kosher => __('Kosher'),
            self::Halal => __('Halal'),
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
        $accepted = $this->coveringOptions();

        foreach ($offered as $option) {
            if (in_array($option, $accepted, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Ofertas que cubren esta necesidad.
     *
     * @return list<self>
     */
    public function coveringOptions(): array
    {
        return match ($this) {
            self::Vegetarian => [self::Vegetarian, self::Vegan],
            self::GlutenFree => [self::GlutenFree, self::SinTacc],
            default => [$this],
        };
    }

    public function hint(): string
    {
        return match ($this) {
            self::Vegan => __('Sin ningún ingrediente de origen animal.'),
            self::Vegetarian => __('Sin carne ni pescado.'),
            self::SinTacc => __('Apto celíacos: se cocina aparte, sin contaminación cruzada.'),
            self::GlutenFree => __('Hay platos sin gluten, pero la cocina no es libre de TACC.'),
            self::LactoseFree => __('Sin leche ni derivados, o con opción deslactosada.'),
            self::Kosher => __('Preparado según las normas kosher.'),
            self::Halal => __('Preparado según las normas halal.'),
        };
    }
}
