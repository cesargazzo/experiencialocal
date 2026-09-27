<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->string('code', 12)->unique(); // Código corto para el participante y el anfitrión.
            $table->foreignId('experience_date_id')->constrained()->restrictOnDelete();
            $table->foreignId('experience_id')->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->unsignedSmallInteger('guests');
            // Todo el desglose queda congelado en la reserva: si cambian precios o planes, no cambia lo pactado.
            $table->decimal('unit_price', 12, 2);
            $table->decimal('subtotal', 12, 2);
            $table->decimal('service_fee_rate', 5, 4);
            $table->decimal('service_fee', 12, 2);
            $table->decimal('total', 12, 2);              // Lo que paga el participante.
            $table->decimal('commission_rate', 5, 4);     // Comisión del plan del anfitrión.
            $table->decimal('commission_amount', 12, 2);
            $table->decimal('host_payout', 12, 2);        // Lo que recibe el anfitrión.
            $table->char('currency', 3)->default('ARS');
            $table->string('status', 20)->default('requested');
            $table->text('guest_note')->nullable();
            $table->string('payment_provider', 30)->nullable();
            $table->string('payment_reference')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('declined_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('refunded_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['experience_id', 'status']);
        });

        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            // Solo quien asistió a una reserva completada puede opinar, y una sola vez.
            $table->foreignId('booking_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('experience_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('rating');
            $table->text('body')->nullable();
            $table->text('host_reply')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reviews');
        Schema::dropIfExists('bookings');
    }
};
