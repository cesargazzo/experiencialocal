<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['slug', 'name', 'icon', 'has_food', 'has_difficulty', 'sort_order'])]
class Category extends Model
{
    use Auditable;

    protected function casts(): array
    {
        return ['has_food' => 'boolean', 'has_difficulty' => 'boolean'];
    }

    public function experiences(): HasMany
    {
        return $this->hasMany(Experience::class);
    }
}
