<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Cada experiencia tiene su política de cancelación y cada reserva guarda la
     * que regía al reservar, más el porcentaje que corresponde devolver si se cancela.
     */
    public function up(): void
    {
        Schema::table('experiences', function (Blueprint $table) {
            $table->string('cancellation_policy', 20)->default('moderate')->after('max_guests');
        });

        Schema::table('bookings', function (Blueprint $table) {
            $table->string('cancellation_policy', 20)->nullable()->after('status');
            $table->unsignedTinyInteger('refund_percent')->nullable()->after('cancellation_policy');
        });

        DB::table('bookings')->update(['cancellation_policy' => 'moderate']);
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn(['cancellation_policy', 'refund_percent']);
        });

        Schema::table('experiences', function (Blueprint $table) {
            $table->dropColumn('cancellation_policy');
        });
    }
};
