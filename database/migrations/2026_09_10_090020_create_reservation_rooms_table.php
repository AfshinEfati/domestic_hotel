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
                ->constrained('reservation_hotels')
                ->cascadeOnDelete();
            $table->foreignId('room_type_id')
                ->nullable()
                ->constrained('room_types')
                ->nullOnDelete();
            $table->foreignId('rate_plan_id')
                ->nullable()
                ->constrained('rate_plans')
                ->nullOnDelete();
            $table->string('room_name')->nullable();
            $table->unsignedSmallInteger('quantity');
            $table->date('check_in');
            $table->date('check_out');
            $table->timestamps();

            $table->index(['reservation_hotel_id', 'check_in', 'check_out']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reservation_rooms');
    }
};
