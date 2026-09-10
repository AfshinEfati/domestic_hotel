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
                ->constrained('reservations')
                ->cascadeOnDelete();
            $table->foreignId('accommodation_id')
                ->constrained('accommodations')
                ->restrictOnDelete();
            $table->unsignedTinyInteger('type');
            $table->boolean('is_final')->index();
            $table->timestamps();

            $table->unique(['reservation_id', 'accommodation_id']);
            $table->index(['reservation_id', 'is_final']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reservation_hotels');
    }
};
