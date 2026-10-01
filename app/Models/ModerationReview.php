<?php

namespace App\Models;

use App\Enums\ModerationVerdict;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

#[Fillable(['verdict', 'categories', 'reason', 'model'])]
class ModerationReview extends Model
{
    public const UPDATED_AT = null;

    protected function casts(): array
    {
        return [
            'verdict' => ModerationVerdict::class,
            'categories' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function reviewable(): MorphTo
    {
        return $this->morphTo();
    }
}
