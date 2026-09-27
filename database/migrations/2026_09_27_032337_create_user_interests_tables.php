<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('category_user', function (Blueprint $table) {
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->primary(['user_id', 'category_id']);
        });

        Schema::create('province_user', function (Blueprint $table) {
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('province_id')->constrained()->cascadeOnDelete();
            $table->primary(['user_id', 'province_id']);
        });

        // Qué experiencia ya se le avisó a cada persona, para no repetir.
        Schema::create('interest_notifications', function (Blueprint $table) {
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('experience_id')->constrained()->cascadeOnDelete();
            $table->timestamp('notified_at');
            $table->primary(['user_id', 'experience_id']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->boolean('interest_alerts')->default(true)->after('invited_by');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('interest_alerts');
        });
        Schema::dropIfExists('interest_notifications');
        Schema::dropIfExists('province_user');
        Schema::dropIfExists('category_user');
    }
};
