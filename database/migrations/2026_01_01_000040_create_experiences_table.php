<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 40)->unique();
            $table->string('name', 60);
            $table->string('icon', 8)->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('experiences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('host_profile_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->constrained();
            $table->string('title', 120);
            $table->string('slug', 140)->unique();
            $table->string('type_label', 60)->nullable(); // "Cocina regional", "Paseo de día completo"
            $table->string('summary', 200);
            $table->text('description');
            $table->string('city', 80);
            $table->string('province', 80)->nullable();
            $table->char('country_code', 2)->default('AR');
            $table->decimal('price', 12, 2);
            $table->char('currency', 3)->default('ARS');
            $table->unsignedSmallInteger('duration_minutes');
            $table->unsignedSmallInteger('max_guests');
            $table->jsonb('includes')->nullable(); // [{label, text}]
            $table->string('cover_image_url')->nullable();
            $table->string('status', 20)->default('draft');
            $table->decimal('rating_avg', 3, 2)->default(0);
            $table->unsignedInteger('reviews_count')->default(0);
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'category_id']);
            $table->index('city');
        });

        Schema::create('experience_dates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('experience_id')->constrained()->cascadeOnDelete();
            $table->timestamp('starts_at');
            $table->timestamp('ends_at')->nullable();
            $table->unsignedSmallInteger('capacity');
            $table->unsignedSmallInteger('booked_count')->default(0);
            $table->string('status', 20)->default('open'); // open, cancelled, done
            $table->timestamps();

            $table->index(['experience_id', 'starts_at']);
        });

        // Un cupo nunca puede quedar sobrevendido, aun con reservas simultáneas.
        DB::statement('ALTER TABLE experience_dates ADD CONSTRAINT experience_dates_capacity_check CHECK (booked_count <= capacity)');
    }

    public function down(): void
    {
        Schema::dropIfExists('experience_dates');
        Schema::dropIfExists('experiences');
        Schema::dropIfExists('categories');
    }
};
