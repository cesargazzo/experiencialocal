<?php

namespace App\Models\Concerns;

use App\Models\ModerationReview;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

/** Revisiones automáticas con IA de este contenido. */
trait HasModerationReviews
{
    public function moderationReviews(): MorphMany
    {
        return $this->morphMany(ModerationReview::class, 'reviewable');
    }

    public function latestModeration(): MorphOne
    {
        return $this->morphOne(ModerationReview::class, 'reviewable')->latestOfMany();
    }
}
