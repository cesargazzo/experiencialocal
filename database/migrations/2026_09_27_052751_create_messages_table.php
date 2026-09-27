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
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sender_id')->constrained('users')->cascadeOnDelete();
            // Cifrado con la clave de la aplicación: en la base no se lee.
            $table->text('body');
            // Se ocultaron teléfonos, mails o enlaces porque todavía no había reserva confirmada.
            $table->boolean('contact_redacted')->default(false);
            $table->timestamp('reported_at')->nullable()->index();
            $table->foreignId('reported_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('report_reason', 300)->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index(['conversation_id', 'id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
