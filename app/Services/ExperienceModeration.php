<?php

namespace App\Services;

use App\Enums\ExperienceStatus;
use App\Models\Experience;
use App\Models\User;
use App\Notifications\ExperienceReviewedNotification;

/**
 * Revisión de contenido de las experiencias antes de publicarse. Hoy la hace
 * un administrador; sin revisor (null) queda lista para una revisión automática.
 */
class ExperienceModeration
{
    /**
     * Aprueba el contenido. Se publica si el anfitrión ya está activo; si no,
     * se publica sola cuando el anfitrión valide su domicilio.
     */
    public function approve(Experience $experience, ?User $reviewer = null): void
    {
        $hostIsActive = $experience->host->isActive();

        $experience->update([
            'approved_at' => now(),
            'approved_by' => $reviewer?->id,
            'rejection_reason' => null,
            'status' => $hostIsActive ? ExperienceStatus::Published : ExperienceStatus::InReview,
            'published_at' => $hostIsActive ? now() : null,
        ]);

        $experience->host->user->notify(new ExperienceReviewedNotification($experience, approved: true));
    }

    /** La devuelve a borrador con el motivo, para que el anfitrión la corrija. */
    public function reject(Experience $experience, string $reason, ?User $reviewer = null): void
    {
        $experience->update([
            'approved_at' => null,
            'approved_by' => $reviewer?->id,
            'rejection_reason' => $reason,
            'status' => ExperienceStatus::Draft,
            'published_at' => null,
        ]);

        $experience->host->user->notify(new ExperienceReviewedNotification($experience, approved: false));
    }
}
