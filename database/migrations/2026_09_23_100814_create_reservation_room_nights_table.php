<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reservation_room_nights', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('reservation_room_id')
                ->comment('Reservation room this night belongs to')
                ->constrained('reservation_rooms')
                ->cascadeOnDelete();

            $table->date('date')
                ->comment('Stay date this row prices');

            // No FK: the selected calendar is a historical snapshot and may be pruned later.
            $table->unsignedBigInteger('room_calendar_id')
                ->nullable()
                ->comment('Calendar selected for this specific night at request time');

            $table->unsignedBigInteger('initial_price')
                ->comment('Per-night price sent by requester in IRR');

            $table->unsignedBigInteger('validated_price')
                ->nullable()
                ->comment('Fresh validated per-night price in IRR');

            $table->timestamps();

            $table->unique(
                ['reservation_room_id', 'date'],
                'reservation_room_nights_room_date_unique'
            );

            $table->index('room_calendar_id', 'reservation_room_nights_calendar_lookup_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reservation_room_nights');
    }
};
