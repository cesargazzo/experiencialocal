<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * El teléfono pasa a guardarse cifrado, con una huella con clave para buscarlo.
     * Las huellas de documentos pasan a llevar clave (antes eran un SHA-256 simple,
     * que con un DNI se podía revertir probando todos los números). Además se borran
     * los teléfonos que habían quedado en texto plano en la auditoría y en los envíos de códigos.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->text('phone')->nullable()->change();
            $table->string('phone_hash', 64)->nullable()->after('phone')->index();
        });

        $key = 'tinku-private-hash|'.config('app.key');

        DB::table('users')->whereNotNull('phone')->orderBy('id')->chunkById(200, function ($users) use ($key) {
            foreach ($users as $user) {
                $digits = preg_replace('/\D/', '', $user->phone);
                DB::table('users')->where('id', $user->id)->update([
                    'phone' => Crypt::encryptString($user->phone),
                    'phone_hash' => strlen($digits) >= 8 ? hash_hmac('sha256', 'phone|'.substr($digits, -10), $key) : null,
                ]);
            }
        });

        DB::table('identity_verifications')->whereNotNull('document_hash')->orderBy('id')->chunkById(200, function ($verifications) use ($key) {
            foreach ($verifications as $verification) {
                DB::table('identity_verifications')->where('id', $verification->id)->update(['document_hash' => hash_hmac('sha256', $verification->document_hash, $key)]);
            }
        });

        DB::table('identity_verifications')->where('type', 'phone')->whereNotNull('result')->orderBy('id')->chunkById(200, function ($verifications) {
            foreach ($verifications as $verification) {
                $result = json_decode($verification->result, true) ?: [];
                if (isset($result['sent_to'])) {
                    $digits = preg_replace('/\D/', '', (string) $result['sent_to']);
                    $result['sent_to'] = $digits === '' ? null : '•••• '.substr($digits, -4);
                    DB::table('identity_verifications')->where('id', $verification->id)->update(['result' => json_encode($result)]);
                }
            }
        });

        foreach (['old_values', 'new_values'] as $column) {
            DB::table('audit_logs')->where('auditable_type', 'App\\Models\\User')->whereRaw("jsonb_exists({$column}, 'phone')")
                ->update([$column => DB::raw("jsonb_set({$column}, '{phone}', '\"*** (dato protegido)\"')")]);
        }
    }

    public function down(): void
    {
        DB::table('users')->whereNotNull('phone')->orderBy('id')->chunkById(200, function ($users) {
            foreach ($users as $user) {
                DB::table('users')->where('id', $user->id)->update(['phone' => mb_substr(Crypt::decryptString($user->phone), 0, 32)]);
            }
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('phone_hash');
            $table->string('phone', 32)->nullable()->change();
        });
        // Las huellas con clave no se pueden volver atrás: quedan como están.
    }
};
