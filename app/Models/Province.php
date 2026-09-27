<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['country_code', 'code', 'name', 'timezone'])]
class Province extends Model
{
    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class, 'country_code');
    }
}
