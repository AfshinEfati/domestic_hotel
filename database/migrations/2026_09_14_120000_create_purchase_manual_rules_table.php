<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_manual_rules', function (Blueprint $table) {
            $table->id();

            $table->string('name', 150)
                ->comment('Manual purchase rule name');

            $table->foreignId('provider_id')
                ->nullable()
                ->comment('Provider identifier for this manual purchase rule')
                ->constrained('providers')
                ->nullOnDelete();

            $table->foreignId('hotel_id')
                ->nullable()
                ->comment('Hotel identifier for this manual purchase rule')
                ->constrained('hotels')
                ->nullOnDelete();

            $table->unsignedBigInteger('minimum_amount')
                ->nullable()
                ->comment('Minimum sale amount for applying this manual rule');

            $table->unsignedBigInteger('maximum_amount')
                ->nullable()
                ->comment('Maximum sale amount for applying this manual rule');

            $table->time('start_time')
                ->nullable()
                ->comment('Daily start time for manual purchase rule');

            $table->time('end_time')
                ->nullable()
                ->comment('Daily end time for manual purchase rule');

            $table->boolean('is_active')
                ->default(true)
                ->comment('Indicates whether the rule is active');

            $table->timestamps();

            $table->index(['provider_id', 'is_active'], 'purchase_manual_rules_provider_active_idx');
            $table->index(['hotel_id', 'is_active'], 'purchase_manual_rules_hotel_active_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_manual_rules');
    }
};
