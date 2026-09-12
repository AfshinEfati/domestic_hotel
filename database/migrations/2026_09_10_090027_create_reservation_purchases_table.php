<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reservation_purchases', function (Blueprint $table) {
            $table->id();

            $table->foreignId('reservation_hotel_id')
                ->comment('Reservation hotel that this purchase belongs to')
                ->constrained('reservation_hotels')
                ->cascadeOnDelete();

            $table->foreignId('provider_id')
                ->comment('Provider used to fulfill the reservation purchase')
                ->constrained('providers')
                ->restrictOnDelete();

            $table->unsignedTinyInteger('status')
                ->index()
                ->comment('Current purchase status using the reservation status workflow');

            $table->foreignId('quoted_provider_id')
                ->nullable()
                ->comment('Provider that supplied the original API quote before procurement')
                ->constrained('providers')
                ->restrictOnDelete();

            $table->unsignedBigInteger('provider_quoted_amount')
                ->nullable()
                ->comment('Original amount returned by the quoted provider API in IRR');

            $table->unsignedBigInteger('purchase_amount')
                ->nullable()
                ->comment('Actual final procurement amount in IRR');

            $table->string('confirmation_code', 100)
                ->nullable()
                ->index()
                ->comment('Confirmation or booking reference received from the provider or hotel');

            $table->string('provider_status', 100)
                ->nullable()
                ->comment('Raw status received from the provider');

            $table->timestamp('expires_at')
                ->nullable()
                ->comment('Expiration time of the provider reservation or temporary hold');

            $table->timestamp('issued_at')
                ->nullable()
                ->comment('Time when the purchase was successfully issued');

            $table->timestamps();

            $table->index(
                ['reservation_hotel_id', 'status'],
                'reservation_purchases_hotel_status_idx'
            );

            $table->index(
                ['provider_id', 'status'],
                'reservation_purchases_provider_status_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reservation_purchases');
    }
};
