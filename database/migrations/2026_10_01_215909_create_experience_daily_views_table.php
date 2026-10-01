<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Visitas por experiencia y por día, para las estadísticas del anfitrión.
     * Es solo un contador: no se guarda quién la miró.
     */
    public function up(): void
    {
        Schema::create('experience_daily_views', function (Blueprint $table) {
            $table->foreignId('experience_id')->constrained()->cascadeOnDelete();
            $table->date('day');
            $table->unsignedInteger('views')->default(0);
            $table->primary(['experience_id', 'day']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('experience_daily_views');
    }
};
