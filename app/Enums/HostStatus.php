<?php

namespace App\Enums;

enum HostStatus: string
{
    case Draft = 'draft';
    case InReview = 'in_review';
    case Active = 'active';
    case Suspended = 'suspended';
}
