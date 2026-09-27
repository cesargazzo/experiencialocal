<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Las versiones de cada foto se generan en segundo plano. El estado dice si
 * ya se pueden mostrar; la rotación se aplica al regenerarlas desde el original.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('media', function (Blueprint $table) {
            // processing: recién subida, todavía sin versiones.
            // ready: lista. reprocessing: visible mientras se regenera (por ejemplo, al girarla).
            // failed: no se pudo procesar.
            $table->string('status', 20)->default('ready')->after('collection');
            $table->unsignedSmallInteger('rotation')->default(0)->after('status');
            $table->text('error')->nullable()->after('alt');
            $table->jsonb('variants')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('media', function (Blueprint $table) {
            $table->dropColumn(['status', 'rotation', 'error']);
        });
    }
};
