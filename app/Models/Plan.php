<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['slug', 'name', 'tagline', 'monthly_price', 'currency', 'commission_rate', 'max_experiences', 'features', 'is_featured', 'is_active', 'sort_order'])]
class Plan extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'monthly_price' => 'decimal:2',
            'commission_rate' => 'decimal:4',
            'features' => 'array',
            'is_featured' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function hostProfiles(): HasMany
    {
        return $this->hasMany(HostProfile::class);
    }

    public function commissionPercent(): int
    {
        return (int) round((float) $this->commission_rate * 100);
    }

    /** Cuánto recibe el anfitrión por una reserva de este monto. */
    public function hostPayoutFor(float $amount): float
    {
        return round($amount - $amount * (float) $this->commission_rate, 2);
    }
}
