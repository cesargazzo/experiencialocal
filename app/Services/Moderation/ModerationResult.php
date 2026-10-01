<?php

namespace App\Services\Moderation;

use App\Enums\ModerationVerdict;

/** Respuesta de la revisión automática. */
final readonly class ModerationResult
{
    /**
     * @param  list<string>  $categories
     */
    public function __construct(
        public ModerationVerdict $verdict,
        public array $categories,
        public string $reason,
        public string $model,
    ) {}
}
