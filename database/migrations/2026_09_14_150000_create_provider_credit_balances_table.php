<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('provider_credit_balances', function (Blueprint $table) {
            $table->id();

            $table->foreignId('provider_id')
                ->unique()
                ->comment('Provider that this credit balance belongs to')
                ->constrained('providers')
                ->cascadeOnDelete();

            $table->unsignedBigInteger('balance')
                ->default(0)
                ->comment('Last known credit balance for this provider in IRR');

            $table->unsignedBigInteger('low_balance_threshold')
                ->nullable()
                ->comment('Balance threshold in IRR below which a low balance alert should be triggered');

            $table->timestamp('low_balance_notified_at')
                ->nullable()
                ->comment('Last time a low balance alert was sent, used to avoid repeated notifications');

            $table->timestamp('synced_at')
                ->nullable()
                ->comment('Last time the balance was successfully synced from the provider API');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('provider_credit_balances');
    }
};
