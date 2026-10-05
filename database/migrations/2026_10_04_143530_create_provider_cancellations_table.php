<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('provider_cancellations', function (Blueprint $table): void {
            $table->id()->comment('Primary key.');
            $table->foreignId('provider_id')
                ->comment('Provider handling this cancellation workflow.')
                ->constrained('providers')
                ->restrictOnDelete();
            $table->foreignId('reservation_purchase_id')
                ->nullable()
                ->comment('Local reservation purchase associated with this provider cancellation when known.')
                ->constrained('reservation_purchases')
                ->nullOnDelete();
            $table->string('tracking_code', 100)->comment('Provider booking tracking or reservation code used for cancellation.');
            $table->string('provider_cancellation_id', 100)->nullable()->comment('Provider cancellation request identifier when returned.');
            $table->string('status', 50)->nullable()->comment('Latest raw provider cancellation status.');
            $table->boolean('manual')->nullable()->comment('Whether the provider reports manual cancellation review.');
            $table->unsignedBigInteger('service_fee')->nullable()->comment('Provider cancellation service fee in IRR.');
            $table->unsignedBigInteger('user_penalty')->nullable()->comment('Provider cancellation user penalty in IRR.');
            $table->unsignedSmallInteger('user_penalty_percent')->nullable()->comment('Provider cancellation penalty percentage.');
            $table->unsignedBigInteger('user_penalty_total')->nullable()->comment('Total provider cancellation penalty in IRR.');
            $table->unsignedBigInteger('user_refund_amount')->nullable()->comment('Provider cancellation refund amount in IRR.');
            $table->json('provider_rules')->nullable()->comment('Provider cancellation rule snapshot returned by inquiry.');
            $table->timestamp('requested_at')->nullable()->comment('Time the cancellation request was created with the provider.');
            $table->timestamp('last_inquired_at')->nullable()->comment('Time the provider cancellation status was last checked.');
            $table->timestamp('decided_at')->nullable()->comment('Time the cancellation was accepted or rejected by this service.');
            $table->timestamp('finalized_at')->nullable()->comment('Time a final provider cancellation state was observed.');
            $table->timestamp('created_at')->nullable()->comment('Row creation timestamp.');
            $table->timestamp('updated_at')->nullable()->comment('Row last update timestamp.');

            $table->unique(['provider_id', 'tracking_code'], 'provider_cancellations_tracking_unique');
            $table->index(['provider_id', 'status'], 'provider_cancellations_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('provider_cancellations');
    }
};
