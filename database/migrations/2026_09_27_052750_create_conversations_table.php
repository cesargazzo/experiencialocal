<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Una conversación por experiencia y viajero: sirve para consultar antes de reservar y después.
        Schema::create('conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('experience_id')->constrained()->cascadeOnDelete();
            $table->foreignId('guest_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('host_user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('last_message_at')->nullable()->index();
            // Último mensaje leído por cada uno (por id: exacto aunque lleguen dos en el mismo segundo).
            $table->unsignedBigInteger('guest_last_read_id')->default(0);
            $table->unsignedBigInteger('host_last_read_id')->default(0);
            $table->timestamps();

            $table->unique(['experience_id', 'guest_id']);
            $table->index(['host_user_id', 'last_message_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('conversations');
    }
};
