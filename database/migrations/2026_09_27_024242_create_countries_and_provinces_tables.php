<?php

use Database\Seeders\GeographySeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Países y provincias como datos de referencia, y la provincia de perfiles
 * y experiencias pasa de texto libre a una relación. La zona horaria de
 * cada experiencia sale de su provincia.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('countries', function (Blueprint $table) {
            $table->char('code', 2)->primary(); // ISO 3166-1 alpha-2
            $table->string('name', 80);
            $table->char('currency', 3);
            $table->string('default_timezone', 64);
            $table->string('phone_prefix', 6)->nullable();
            $table->boolean('is_active')->default(false);
            $table->timestamps();
        });

        Schema::create('provinces', function (Blueprint $table) {
            $table->id();
            $table->char('country_code', 2);
            $table->foreign('country_code')->references('code')->on('countries')->cascadeOnUpdate();
            $table->string('code', 8)->unique(); // ISO 3166-2
            $table->string('name', 80);
            $table->string('timezone', 64);
            $table->timestamps();

            $table->index(['country_code', 'name']);
        });

        (new GeographySeeder)->run();

        foreach (['host_profiles', 'experiences'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->foreignId('province_id')->nullable()->after('city')->constrained();
            });

            // Las filas existentes guardaban el nombre de la provincia como texto.
            DB::table($tableName)->whereNotNull('province')->orderBy('id')->each(function (object $row) use ($tableName) {
                $provinceId = DB::table('provinces')->whereRaw('lower(name) = lower(?)', [trim($row->province)])->value('id');
                DB::table($tableName)->where('id', $row->id)->update(['province_id' => $provinceId]);
            });

            Schema::table($tableName, function (Blueprint $table) {
                $table->dropColumn('province');
            });
        }
    }

    public function down(): void
    {
        foreach (['host_profiles', 'experiences'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->string('province', 80)->nullable()->after('city');
            });

            DB::table($tableName)->whereNotNull('province_id')->orderBy('id')->each(function (object $row) use ($tableName) {
                DB::table($tableName)->where('id', $row->id)->update([
                    'province' => DB::table('provinces')->where('id', $row->province_id)->value('name'),
                ]);
            });

            Schema::table($tableName, function (Blueprint $table) {
                $table->dropConstrainedForeignId('province_id');
            });
        }

        Schema::dropIfExists('provinces');
        Schema::dropIfExists('countries');
    }
};
