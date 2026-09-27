<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->string('auditable_type', 120);
            $table->string('auditable_id', 64); // texto: algunas tablas usan claves como "AR" o "password_policy".
            $table->string('event', 40); // created, updated, deleted u otros como interests.updated
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete(); // quién lo hizo
            $table->jsonb('old_values')->nullable();
            $table->jsonb('new_values')->nullable();
            $table->string('source', 10); // web, consola
            $table->string('ip', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('path', 500)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['auditable_type', 'auditable_id', 'created_at']);
            $table->index(['user_id', 'created_at']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
