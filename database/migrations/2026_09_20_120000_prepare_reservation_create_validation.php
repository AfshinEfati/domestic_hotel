<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reservations', function (Blueprint $table): void {
            // Legacy integrations may still read reservation_number. New reservations mirror their primary key.
            $table->string('reservation_number', 100)->nullable()->change();
            $table->unsignedBigInteger('initial_sale_amount')->nullable()->comment('Original total quoted by requester in IRR');
            $table->unsignedBigInteger('validated_sale_amount')->nullable()->comment('Fresh validated total before retaining any price decrease, in IRR');
            $table->string('validation_error', 255)->nullable()->comment('Reason the initial availability/price check did not complete');
        });

        Schema::table('reservation_rooms', function (Blueprint $table): void {
            // No FK: expired room_calendars are pruned; the selected calendar ID is a historical snapshot.
            $table->unsignedBigInteger('room_calendar_id')->nullable()->comment('Calendar selected on the first night at request time');
            $table->unsignedBigInteger('provider_id')->nullable()->comment('Original provider resolved internally from the selected calendar');
            $table->unsignedBigInteger('initial_price')->nullable()->comment('Original full-stay price sent by requester in IRR');
            $table->unsignedBigInteger('validated_price')->nullable()->comment('Fresh full-stay validated price in IRR');
            $table->index('room_calendar_id', 'reservation_rooms_calendar_lookup_idx');
        });
    }

    public function down(): void
    {
        Schema::table('reservation_rooms', function (Blueprint $table): void {
            $table->dropIndex('reservation_rooms_calendar_lookup_idx');
            $table->dropColumn(['room_calendar_id', 'provider_id', 'initial_price', 'validated_price']);
        });

        Schema::table('reservations', function (Blueprint $table): void {
            $table->dropColumn(['initial_sale_amount', 'validated_sale_amount', 'validation_error']);
            // Historical nulls must be reconciled before reverting the nullable legacy column.
        });
    }
};
