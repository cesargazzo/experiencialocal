<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * El manual de marca pide íconos Phosphor en vez de emoji: la columna guarda
 * ahora el nombre del ícono, y las categorías existentes se actualizan.
 */
return new class extends Migration
{
    /** @var array<string, string> */
    private array $icons = [
        'comida' => 'fork-knife',
        'cocina' => 'cooking-pot',
        'paseo' => 'mountains',
        'taller' => 'yarn',
    ];

    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->string('icon', 40)->nullable()->change();
        });

        foreach ($this->icons as $slug => $icon) {
            DB::table('categories')->where('slug', $slug)->update(['icon' => $icon]);
        }
    }

    public function down(): void
    {
        DB::table('categories')->update(['icon' => null]);

        Schema::table('categories', function (Blueprint $table) {
            $table->string('icon', 8)->nullable()->change();
        });
    }
};
