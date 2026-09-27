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
        Schema::table('experiences', function (Blueprint $table) {
            $table->jsonb('dietary_options')->nullable()->after('includes');
            $table->timestamp('approved_at')->nullable()->after('published_at');
            $table->foreignId('approved_by')->nullable()->after('approved_at')->constrained('users')->nullOnDelete();
            $table->string('rejection_reason', 500)->nullable()->after('approved_by');
        });

        // Lo que ya estaba publicado se da por aprobado.
        DB::table('experiences')->where('status', 'published')->update(['approved_at' => DB::raw('coalesce(published_at, created_at)')]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('experiences', function (Blueprint $table) {
            $table->dropConstrainedForeignId('approved_by');
            $table->dropColumn(['dietary_options', 'approved_at', 'rejection_reason']);
        });
    }
};
