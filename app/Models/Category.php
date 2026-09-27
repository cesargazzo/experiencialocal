<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['slug', 'name', 'icon', 'sort_order'])]
class Category extends Model
{
    use Auditable;

    public function experiences(): HasMany
    {
        return $this->hasMany(Experience::class);
    }
}
