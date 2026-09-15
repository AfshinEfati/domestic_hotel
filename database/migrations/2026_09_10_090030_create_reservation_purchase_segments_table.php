<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reservation_purchase_segments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('reservation_purchase_id')
                ->comment('Reservation purchase that fulfills this room segment')
                ->constrained('reservation_purchases')
                ->cascadeOnDelete();

            $table->foreignId('reservation_room_id')
                ->comment('Reservation room fulfilled by this purchase segment')
                ->constrained('reservation_rooms')
                ->cascadeOnDelete();

            $table->date('from_date')
                ->comment('First stay date covered by this purchase segment');

            $table->date('to_date')
                ->comment('End-exclusive stay date covered by this purchase segment');

            $table->unsignedBigInteger('nightly_purchase_amount')
                ->nullable()
                ->comment('Procurement amount per night for this room segment in IRR');

            $table->unsignedBigInteger('nightly_extra_bed_purchase_amount')
                ->nullable()
                ->comment('Procurement amount per night for an extra bed in IRR');

            $table->unsignedBigInteger('nightly_child_purchase_amount')
                ->nullable()
                ->comment('Procurement amount per night for a child in IRR');

            $table->timestamps();

            $table->unique(
                [
                    'reservation_purchase_id',
                    'reservation_room_id',
                    'from_date',
                    'to_date',
                ],
                'reservation_purchase_segments_unique'
            );

            $table->index(
                ['reservation_room_id', 'from_date', 'to_date'],
                'reservation_purchase_segments_room_dates_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reservation_purchase_segments');
    }
};
