<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Punto de encuentro exacto: solo lo ve quien tiene la reserva confirmada.
        Schema::table('experiences', function (Blueprint $table) {
            $table->string('meeting_address', 200)->nullable()->after('province_id');
            $table->decimal('latitude', 10, 7)->nullable()->after('meeting_address');
            $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
            $table->timestamp('address_normalized_at')->nullable()->after('longitude');
        });

        // Arranca con el domicilio del anfitrión; después cada experiencia marca el suyo.
        DB::statement('update experiences set meeting_address = host_profiles.address from host_profiles where host_profiles.id = experiences.host_profile_id and experiences.meeting_address is null');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('experiences', function (Blueprint $table) {
            $table->dropColumn(['meeting_address', 'latitude', 'longitude', 'address_normalized_at']);
        });
    }
};
