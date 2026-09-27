<?php

namespace App\Models;

use App\Enums\VerificationProvider;
use App\Enums\VerificationStatus;
use App\Enums\VerificationType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id', 'type', 'provider', 'status', 'document_country', 'document_type', 'document_hash',
    'provider_reference', 'result', 'rejection_reason', 'reviewed_by', 'submitted_at', 'reviewed_at', 'expires_at',
])]
class IdentityVerification extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'type' => VerificationType::class,
            'provider' => VerificationProvider::class,
            'status' => VerificationStatus::class,
            'result' => 'array',
            'submitted_at' => 'datetime',
            'reviewed_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function isApproved(): bool
    {
        return $this->status === VerificationStatus::Approved
            && ($this->expires_at === null || $this->expires_at->isFuture());
    }
}
