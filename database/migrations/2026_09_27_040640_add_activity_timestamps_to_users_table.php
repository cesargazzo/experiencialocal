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
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('last_login_at')->nullable()->index();
            $table->timestamp('last_seen_at')->nullable();
        });

        // Lo que ya estaba en el registro de seguridad.
        DB::statement(<<<'SQL'
            update users set last_login_at = events.last_login
            from (
                select user_id, max(created_at) as last_login
                from security_events
                where type = 'login.succeeded' and user_id is not null
                group by user_id
            ) as events
            where events.user_id = users.id
        SQL);
        DB::table('users')->update(['last_seen_at' => DB::raw('last_login_at')]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['last_login_at', 'last_seen_at']);
        });
    }
};
