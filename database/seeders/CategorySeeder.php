<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['slug' => 'comida', 'name' => 'Comidas', 'icon' => 'fork-knife', 'sort_order' => 1],
            ['slug' => 'cocina', 'name' => 'Clases de cocina', 'icon' => 'cooking-pot', 'sort_order' => 2],
            ['slug' => 'paseo', 'name' => 'Paseos y viajes', 'icon' => 'mountains', 'sort_order' => 3],
            ['slug' => 'taller', 'name' => 'Talleres', 'icon' => 'yarn', 'sort_order' => 4],
        ];

        foreach ($categories as $category) {
            Category::updateOrCreate(['slug' => $category['slug']], $category);
        }
    }
}
