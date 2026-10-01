<?php

namespace App\Enums;

/** Qué tan exigente es físicamente una experiencia, sobre todo en paseos. */
enum Difficulty: string
{
    case Easy = 'easy';
    case Moderate = 'moderate';
    case Hard = 'hard';
    case Expert = 'expert';

    public function label(): string
    {
        return match ($this) {
            self::Easy => __('Baja'),
            self::Moderate => __('Media'),
            self::Hard => __('Alta'),
            self::Expert => __('Exigente'),
        };
    }

    public function hint(): string
    {
        return match ($this) {
            self::Easy => __('Caminata tranquila y terreno parejo. Apta para casi todos.'),
            self::Moderate => __('Algunas subidas o varias horas en movimiento.'),
            self::Hard => __('Terreno irregular o desnivel. Hace falta buen estado físico.'),
            self::Expert => __('Para personas con experiencia en montaña o trekking.'),
        };
    }
}
