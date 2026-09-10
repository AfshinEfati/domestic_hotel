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
            $table->foreignId('reservation_room_id')
                ->constrained('reservation_rooms')
                ->cascadeOnDelete();
            $table->foreignId('provider_id')
                ->nullable()
                ->constrained('providers')
                ->nullOnDelete();
            $table->unsignedTinyInteger('purchase_method');
            $table->unsignedSmallInteger('quantity');
            $table->date('from_date');
            $table->date('to_date');
            $table->string('provider_reference')->nullable();
            $table->string('provider_property_id')->nullable();
            $table->string('provider_room_type_id')->nullable();
            $table->string('provider_rate_plan_id')->nullable();
            $table->timestamp('purchased_at')->nullable();
            $table->timestamp('issued_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['reservation_room_id', 'from_date', 'to_date']);
            $table->index(['provider_id', 'purchase_method']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reservation_purchase_segments');
    }
};
