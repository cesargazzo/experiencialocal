<?php

namespace App\Policies;

use App\Models\Experience;
use App\Models\User;

class ExperiencePolicy
{
    /** Solo el anfitrión dueño edita la experiencia y sus fechas. */
    public function update(User $user, Experience $experience): bool
    {
        return $experience->host?->user_id === $user->id && ! $user->isSuspended();
    }
}
