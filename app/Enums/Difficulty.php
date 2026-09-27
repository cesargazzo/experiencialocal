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
            self::Easy => 'Baja',
            self::Moderate => 'Media',
            self::Hard => 'Alta',
            self::Expert => 'Exigente',
        };
    }

    public function hint(): string
    {
        return match ($this) {
            self::Easy => 'Caminata tranquila y terreno parejo. Apta para casi todos.',
            self::Moderate => 'Algunas subidas o varias horas en movimiento.',
            self::Hard => 'Terreno irregular o desnivel. Hace falta buen estado físico.',
            self::Expert => 'Para personas con experiencia en montaña o trekking.',
        };
    }
}
