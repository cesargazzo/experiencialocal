<?php

namespace App\Enums;

enum ExperienceStatus: string
{
    case Draft = 'draft';
    case InReview = 'in_review';
    case Published = 'published';
    case Paused = 'paused';
    case Archived = 'archived';
}
