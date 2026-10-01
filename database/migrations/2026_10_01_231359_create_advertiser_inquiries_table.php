<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Consultas de marcas, organismos de turismo o comercios que quieren anunciar en Tinku.
     */
    public function up(): void
    {
        Schema::create('advertiser_inquiries', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->string('company', 160);
            $table->string('email');
            $table->text('phone')->nullable(); // cifrado
            $table->jsonb('formats')->nullable();
            $table->string('budget', 20)->nullable();
            $table->text('message');
            $table->string('status', 20)->default('new')->index(); // new, contacted, closed
            $table->foreignId('handled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('ip', 45)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('advertiser_inquiries');
    }
};
