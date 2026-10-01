<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Lo que dijo la revisión automática con IA de cada experiencia, foto o mensaje.
     * Es un aviso para el equipo: la decisión final sobre experiencias sigue siendo humana.
     */
    public function up(): void
    {
        Schema::create('moderation_reviews', function (Blueprint $table) {
            $table->id();
            $table->morphs('reviewable');
            $table->string('verdict', 10); // allow, review, block
            $table->jsonb('categories')->nullable();
            $table->string('reason', 500)->nullable();
            $table->string('model', 60);
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('moderation_reviews');
    }
};
