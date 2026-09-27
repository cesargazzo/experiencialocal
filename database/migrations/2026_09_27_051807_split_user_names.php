<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Nombre y apellido por separado. "name" queda como el nombre completo,
     * armado a partir de los dos, para lo que ya lo usaba.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('first_name', 60)->nullable()->after('name');
            $table->string('last_name', 80)->nullable()->after('first_name');
        });

        // Lo existente: la primera palabra es el nombre y el resto el apellido.
        DB::statement(<<<'SQL'
            update users set
                first_name = split_part(trim(name), ' ', 1),
                last_name = nullif(trim(substr(trim(name), length(split_part(trim(name), ' ', 1)) + 1)), '')
        SQL);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['first_name', 'last_name']);
        });
    }
};
