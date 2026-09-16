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

            $table->string('reservation_number', 100)
                ->comment('GDS reservation reference for the manual purchase decision');

            $table->foreign('reservation_number', 'rmr_reservation_number_fk')
                ->references('reservation_number')
                ->on('reservations')
                ->cascadeOnDelete();

            $table->foreignId('provider_id')
                ->comment('Provider selected for this purchase decision')
                ->constrained('providers')
                ->restrictOnDelete();

            $table->string('reason', 255)
                ->comment('Human-readable reason for switching to manual purchase');

            $table->timestamps();

            $table->index(['reservation_number', 'provider_id'], 'rmr_res_provider_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reservation_manual_reasons');
    }
};
