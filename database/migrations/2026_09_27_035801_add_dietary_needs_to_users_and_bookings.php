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
        Schema::table('users', function (Blueprint $table) {
            $table->jsonb('dietary_needs')->nullable()->after('interest_alerts');
            $table->string('food_allergies', 300)->nullable()->after('dietary_needs');
        });

        // Se copian a la reserva para que el anfitrión sepa qué cocinar aunque la persona cambie su perfil después.
        Schema::table('bookings', function (Blueprint $table) {
            $table->jsonb('dietary_needs')->nullable()->after('guest_note');
            $table->string('food_allergies', 300)->nullable()->after('dietary_needs');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['dietary_needs', 'food_allergies']);
        });

        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn(['dietary_needs', 'food_allergies']);
        });
    }
};
