<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Antes de limitar a una reserva activa por persona y fecha se pudieron pedir
     * varias iguales. Se deja una (la confirmada o, si no hay, la más vieja) y el
     * resto se cancela, devolviendo los lugares a la fecha.
     */
    public function up(): void
    {
        DB::transaction(function (): void {
            $groups = DB::table('bookings')
                ->select('user_id', 'experience_date_id')
                ->whereIn('status', ['requested', 'confirmed'])
                ->groupBy('user_id', 'experience_date_id')
                ->havingRaw('count(*) > 1')
                ->get();

            foreach ($groups as $group) {
                $bookings = DB::table('bookings')
                    ->where('user_id', $group->user_id)
                    ->where('experience_date_id', $group->experience_date_id)
                    ->whereIn('status', ['requested', 'confirmed'])
                    ->orderByRaw("case when status = 'confirmed' then 0 else 1 end")
                    ->orderBy('id')
                    ->get(['id', 'guests']);

                $extra = $bookings->slice(1);
                DB::table('bookings')->whereIn('id', $extra->pluck('id'))->update(['status' => 'cancelled', 'cancelled_at' => now(), 'updated_at' => now()]);
                DB::table('experience_dates')->where('id', $group->experience_date_id)
                    ->update(['booked_count' => DB::raw('greatest(booked_count - '.(int) $extra->sum('guests').', 0)')]);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Las reservas canceladas no se reabren.
    }
};
