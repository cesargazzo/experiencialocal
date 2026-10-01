<?php

namespace App\Enums;

/** Qué opina la revisión automática de un contenido. */
enum ModerationVerdict: string
{
    case Allow = 'allow';
    case Review = 'review';
    case Block = 'block';

    public function label(): string
    {
        return match ($this) {
            self::Allow => 'IA: sin problemas',
            self::Review => 'IA: revisar',
            self::Block => 'IA: no publicar',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Allow => 'badge--ok',
            self::Review => 'badge--sev-warning',
            self::Block => 'badge--error',
        };
    }
}
