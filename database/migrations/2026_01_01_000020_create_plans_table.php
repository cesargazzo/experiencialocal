<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 40)->unique();
            $table->string('name', 60);
            $table->string('tagline', 80)->nullable();
            $table->decimal('monthly_price', 12, 2)->default(0);
            $table->char('currency', 3)->default('ARS');
            // Comisión sobre cada reserva, por ejemplo 0.1800 para 18 %.
            $table->decimal('commission_rate', 5, 4);
            $table->unsignedSmallInteger('max_experiences')->nullable(); // null = sin límite
            $table->jsonb('features')->nullable();
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};
