<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Del "es o no es admin" a roles del equipo con permisos mínimos.
     * Quien ya era admin pasa a administración total.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('team_role', 20)->nullable()->after('is_admin')->index();
        });

        DB::table('users')->where('is_admin', true)->update(['team_role' => 'admin']);

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('is_admin');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_admin')->default(false)->after('team_role');
        });

        DB::table('users')->whereNotNull('team_role')->update(['is_admin' => true]);

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('team_role');
        });
    }
};
