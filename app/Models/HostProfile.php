<?php

namespace App\Models;

use App\Enums\HostStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'user_id', 'plan_id', 'display_name', 'bio', 'city', 'province_id', 'country_code', 'address',
    'latitude', 'longitude', 'status', 'payout_holder_name', 'payout_account', 'hosting_since',
])]
class HostProfile extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'status' => HostStatus::class,
            // Dirección y cuenta de cobro nunca se guardan en claro.
            'address' => 'encrypted',
            'payout_account' => 'encrypted',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'hosting_since' => 'datetime',
            'rating_avg' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function province(): BelongsTo
    {
        return $this->belongsTo(Province::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function experiences(): HasMany
    {
        return $this->hasMany(Experience::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function isActive(): bool
    {
        return $this->status === HostStatus::Active;
    }

    public function canPublishAnother(): bool
    {
        $max = $this->plan->max_experiences;

        return $max === null || $this->experiences()->whereNotIn('status', ['archived'])->count() < $max;
    }
}
