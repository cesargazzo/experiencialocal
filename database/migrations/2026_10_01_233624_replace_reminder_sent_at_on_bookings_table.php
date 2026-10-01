<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * De un solo recordatorio (el día anterior) a tres: una semana antes, un día
     * antes y el mismo día. Se anota cuáles ya salieron para no repetirlos.
     */
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->jsonb('reminders_sent')->nullable()->after('food_allergies');
        });

        DB::table('bookings')->whereNotNull('reminder_sent_at')->update(['reminders_sent' => json_encode(['week', 'day'])]);

        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn('reminder_sent_at');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->timestamp('reminder_sent_at')->nullable();
        });

        DB::table('bookings')->whereRaw("reminders_sent @> '[\"day\"]'::jsonb")->update(['reminder_sent_at' => now()]);

        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn('reminders_sent');
        });
    }
};
