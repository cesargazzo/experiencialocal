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

        $experience->host->user->notify(new ExperienceReviewedNotification($experience, ExperienceReviewedNotification::APPROVED));
    }

    /**
     * El anfitrión cambió texto, categoría o foto: vuelve a revisión y deja de
     * verse hasta que se apruebe de nuevo.
     */
    public function resubmit(Experience $experience): void
    {
        $experience->update([
            'status' => ExperienceStatus::InReview,
            'approved_at' => null,
            'approved_by' => null,
            'rejection_reason' => null,
            'published_at' => null,
        ]);
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

        $experience->host->user->notify(new ExperienceReviewedNotification($experience, ExperienceReviewedNotification::REJECTED));
    }

    /** Un administrador la saca de circulación (denuncias, datos falsos, etc.). Las reservas existentes siguen. */
    public function pause(Experience $experience, string $reason, ?User $reviewer = null): void
    {
        $experience->update(['status' => ExperienceStatus::Paused, 'paused_reason' => $reason]);

        $experience->host->user->notify(new ExperienceReviewedNotification($experience, ExperienceReviewedNotification::PAUSED));
    }

    /** Vuelve a verse si ya estaba aprobada y el anfitrión está activo; si no, vuelve a revisión. */
    public function resume(Experience $experience, ?User $reviewer = null): void
    {
        $publish = $experience->approved_at !== null && $experience->host->isActive();

        $experience->update([
            'status' => $publish ? ExperienceStatus::Published : ExperienceStatus::InReview,
            'published_at' => $publish ? ($experience->published_at ?? now()) : null,
            'paused_reason' => null,
        ]);

        $experience->host->user->notify(new ExperienceReviewedNotification($experience, ExperienceReviewedNotification::RESUMED));
    }
}
