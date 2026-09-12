<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reservation_hotels', function (Blueprint $table) {
            $table->id();

            $table->foreignId('reservation_id')
                ->comment('Reservation that this hotel belongs to')
                ->constrained('reservations')
                ->cascadeOnDelete();

            $table->foreignId('accommodation_id')
                ->comment('Accommodation selected or proposed for the reservation')
                ->constrained('accommodations')
                ->restrictOnDelete();

            $table->unsignedTinyInteger('type')
                ->comment('Hotel role in the reservation, such as requested or alternative');

            $table->boolean('is_final')
                ->default(true)
                ->index()
                ->comment('Indicates whether this hotel is the final selected hotel for the reservation');

            $table->timestamps();

            $table->unique(
                ['reservation_id', 'accommodation_id'],
                'reservation_hotels_reservation_accommodation_unique'
            );

            $table->index(
                ['reservation_id', 'is_final'],
                'reservation_hotels_final_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reservation_hotels');
    }
};
