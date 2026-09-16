<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reservation_manual_reasons', function (Blueprint $table) {
            $table->id();

            $table->foreignId('reservation_id')
                ->comment('Reservation whose purchase was switched to manual processing')
                ->constrained('reservations')
                ->cascadeOnDelete();

            $table->string('reason', 255)
                ->comment('Human-readable reason for switching to manual purchase');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reservation_manual_reasons');
    }
};
