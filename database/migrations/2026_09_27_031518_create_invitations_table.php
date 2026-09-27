<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invitations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inviter_id')->constrained('users')->cascadeOnDelete();
            $table->string('channel', 10); // email, link
            $table->string('name', 120)->nullable();
            $table->string('email')->nullable();
            // Se guarda solo el hash del token: con la base no se puede armar el enlace.
            $table->string('token_hash', 64)->unique();
            $table->timestamp('expires_at');
            $table->foreignId('accepted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamps();

            $table->index(['inviter_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invitations');
    }
};
