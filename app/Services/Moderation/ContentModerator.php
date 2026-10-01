<?php

namespace App\Services\Moderation;

/**
 * Revisa contenido de la gente antes de que otra persona lo vea.
 * La implementación real usa Claude; en las pruebas se reemplaza.
 */
interface ContentModerator
{
    /**
     * @param  string  $kind  Qué es: "experiencia" o "mensaje".
     * @param  array<string, string|null>  $fields  Textos a revisar, por nombre de campo.
     * @param  array{data: string, media_type: string}|null  $image  Foto en base64, si hay.
     */
    public function review(string $kind, array $fields, ?array $image = null): ModerationResult;
}
