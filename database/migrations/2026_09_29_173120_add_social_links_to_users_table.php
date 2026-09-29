<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Redes y web declaradas: {"instagram": {"url": "...", "public": true}, ...}.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->jsonb('social_links')->nullable()->after('food_allergies');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('social_links');
        });
    }
};
