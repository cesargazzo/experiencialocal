<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Qué datos pide cada categoría: comida para comidas y clases, dificultad para paseos.
        Schema::table('categories', function (Blueprint $table) {
            $table->boolean('has_food')->default(false)->after('icon');
            $table->boolean('has_difficulty')->default(false)->after('has_food');
        });
        DB::table('categories')->whereIn('slug', ['comida', 'cocina'])->update(['has_food' => true]);
        DB::table('categories')->where('slug', 'paseo')->update(['has_difficulty' => true]);

        Schema::table('experiences', function (Blueprint $table) {
            $table->string('difficulty', 20)->nullable()->after('dietary_options');
            $table->string('what_to_bring', 500)->nullable()->after('difficulty');
            $table->unsignedSmallInteger('min_age')->nullable()->after('what_to_bring');
            $table->jsonb('features')->nullable()->after('min_age');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('experiences', function (Blueprint $table) {
            $table->dropColumn(['difficulty', 'what_to_bring', 'min_age', 'features']);
        });
        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn(['has_food', 'has_difficulty']);
        });
    }
};
