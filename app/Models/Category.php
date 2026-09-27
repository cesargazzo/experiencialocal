<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['slug', 'name', 'icon', 'sort_order'])]
class Category extends Model
{
    public function experiences(): HasMany
    {
        return $this->hasMany(Experience::class);
    }
}
