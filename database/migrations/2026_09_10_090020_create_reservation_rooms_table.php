<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reservation_rooms', function (Blueprint $table) {
            $table->id();

            $table->foreignId('reservation_hotel_id')
                ->comment('Reservation hotel that this room belongs to')
                ->constrained('reservation_hotels')
                ->cascadeOnDelete();

            $table->unsignedSmallInteger('room_number')
                ->comment('Sequential room number within the reservation hotel');

            $table->foreignId('room_type_id')
                ->nullable()
                ->comment('Room type selected for the reservation')
                ->constrained('room_types')
                ->restrictOnDelete();

            $table->foreignId('rate_plan_id')
                ->nullable()
                ->comment('Rate plan selected for the reservation room')
                ->constrained('rate_plans')
                ->restrictOnDelete();

            $table->string('room_name')
                ->nullable()
                ->comment('Manually entered room name when no room type is available');

            $table->timestamps();

            $table->unique(
                ['reservation_hotel_id', 'room_number'],
                'reservation_rooms_hotel_number_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reservation_rooms');
    }
};
