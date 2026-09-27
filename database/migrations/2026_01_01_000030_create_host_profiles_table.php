<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('host_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('plan_id')->constrained();
            $table->string('display_name', 80);
            $table->text('bio')->nullable();
            $table->string('city', 80);
            $table->string('province', 80)->nullable();
            $table->char('country_code', 2)->default('AR');
            // Dirección exacta cifrada en el modelo; solo se comparte con reservas confirmadas.
            $table->text('address')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('status', 20)->default('draft');
            // Datos de cobro. El titular debe coincidir con el documento validado.
            $table->string('payout_holder_name')->nullable();
            $table->text('payout_account')->nullable(); // CBU/CVU/alias, cifrado en el modelo
            $table->timestamp('hosting_since')->nullable();
            $table->decimal('rating_avg', 3, 2)->default(0);
            $table->unsignedInteger('reviews_count')->default(0);
            $table->timestamps();
        });

        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('host_profile_id')->constrained()->cascadeOnDelete();
            $table->foreignId('plan_id')->constrained();
            $table->string('status', 20)->default('active'); // active, past_due, cancelled
            $table->string('provider', 30)->nullable();      // mercadopago
            $table->string('provider_reference')->nullable();
            $table->timestamp('starts_at');
            $table->timestamp('ends_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
        Schema::dropIfExists('host_profiles');
    }
};
