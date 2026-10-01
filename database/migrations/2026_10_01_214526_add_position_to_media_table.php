<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Orden de las fotos en colecciones de varias (la galería de una experiencia).
     */
    public function up(): void
    {
        Schema::table('media', function (Blueprint $table) {
            $table->unsignedSmallInteger('position')->nullable()->after('collection');
        });
    }

    public function down(): void
    {
        Schema::table('media', function (Blueprint $table) {
            $table->dropColumn('position');
        });
    }
};
