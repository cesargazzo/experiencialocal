<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('identity_verifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type', 20);       // email, phone, document, liveness, address, interview
            $table->string('provider', 20);   // internal, renaper, metamap, sumsub, manual
            $table->string('status', 20)->default('pending');
            // Documento: país emisor y tipo. Permite argentinos y extranjeros con el mismo flujo.
            $table->char('document_country', 2)->nullable();
            $table->string('document_type', 20)->nullable(); // dni, passport, national_id, driver_license
            // Nunca se guarda el número completo en claro. Solo un hash para detectar duplicados.
            $table->string('document_hash', 64)->nullable()->index();
            $table->string('provider_reference')->nullable();
            // Resultado crudo del proveedor (sin imágenes). Las imágenes van a storage cifrado.
            $table->jsonb('result')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'type', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('identity_verifications');
    }
};
