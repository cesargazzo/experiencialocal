<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('security_events', function (Blueprint $table) {
            $table->id();
            $table->string('type', 40);
            $table->string('severity', 10)->default('info'); // info, warning, danger
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('email')->nullable(); // El email que se intentó usar, exista o no la cuenta.
            $table->string('ip', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('method', 10)->nullable();
            $table->string('path', 500)->nullable();
            $table->jsonb('metadata')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['type', 'created_at']);
            $table->index(['ip', 'created_at']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('security_events');
    }
};
