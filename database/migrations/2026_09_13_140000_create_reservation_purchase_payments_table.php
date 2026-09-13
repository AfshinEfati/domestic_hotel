<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reservation_purchase_payments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('reservation_purchase_id')
                ->comment('Reservation purchase associated with this payment')
                ->constrained('reservation_purchases')
                ->cascadeOnDelete();

            $table->unsignedBigInteger('amount')
                ->comment('Payment amount in IRR');

            $table->unsignedTinyInteger('source')
                ->comment('Payment source such as credit, bank transfer, online or cash');

            $table->unsignedBigInteger('bank_account_id')
                ->nullable()
                ->comment('Reference identifier of bank account from accounting service');

            $table->unsignedBigInteger('card_id')
                ->nullable()
                ->comment('Reference identifier of bank card from accounting service');

            $table->dateTime('paid_at')
                ->nullable()
                ->comment('Payment date and time');

            $table->string('reference', 150)
                ->nullable()
                ->comment('External payment reference identifier');

            $table->string('receipt_document_id', 100)
                ->nullable()
                ->comment('Payment receipt document reference');

            $table->unsignedTinyInteger('status')
                ->comment('Current payment status');

            $table->text('description')
                ->nullable()
                ->comment('Additional payment information');

            $table->timestamps();

            $table->index(
                ['reservation_purchase_id', 'status'],
                'reservation_purchase_payments_purchase_status_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reservation_purchase_payments');
    }
};
