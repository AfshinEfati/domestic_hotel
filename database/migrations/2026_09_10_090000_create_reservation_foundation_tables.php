<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reservations', function (Blueprint $table) {
            $table->id();
            $table->string('reservation_number', 40)->unique();
            $table->unsignedTinyInteger('status')->default(1)->index();
            $table->unsignedBigInteger('total_price')->nullable();
            $table->string('email')->nullable();
            $table->string('mobile', 32)->nullable();
            $table->timestamps();
        });

        Schema::create('reservation_hotels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reservation_id')
                ->constrained('reservations')
                ->cascadeOnDelete();
            $table->foreignId('accommodation_id')
                ->constrained('accommodations')
                ->restrictOnDelete();
            $table->unsignedTinyInteger('type')->default(1);
            $table->boolean('is_final')->default(false)->index();
            $table->timestamps();

            $table->unique(['reservation_id', 'accommodation_id']);
            $table->index(['reservation_id', 'is_final']);
        });

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
            $table->unsignedSmallInteger('quantity')->default(1);
            $table->date('check_in');
            $table->date('check_out');
            $table->timestamps();

            $table->index(['reservation_hotel_id', 'check_in', 'check_out']);
        });

        Schema::create('reservation_purchase_segments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reservation_room_id')
                ->constrained('reservation_rooms')
                ->cascadeOnDelete();
            $table->foreignId('provider_id')
                ->nullable()
                ->constrained('providers')
                ->nullOnDelete();
            $table->unsignedTinyInteger('purchase_method')->default(1);
            $table->unsignedSmallInteger('quantity')->default(1);
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
        Schema::dropIfExists('reservation_rooms');
        Schema::dropIfExists('reservation_hotels');
        Schema::dropIfExists('reservations');
    }
};
