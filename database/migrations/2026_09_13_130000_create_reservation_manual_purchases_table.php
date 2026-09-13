<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reservation_manual_purchases', function (Blueprint $table) {
            $table->id();

            $table->foreignId('reservation_purchase_id')
                ->comment('Reservation purchase associated with this manual execution')
                ->constrained('reservation_purchases')
                ->cascadeOnDelete();

            $table->string('acc_code', 100)
                ->nullable()
                ->comment('Accountant code of the sales operator who handled the manual purchase');

            $table->dateTime('purchased_at')
                ->nullable()
                ->comment('Date and time when the manual purchase was completed');

            $table->text('description')
                ->nullable()
                ->comment('Additional information about the manual purchase');

            $table->timestamps();

            $table->unique(
                'reservation_purchase_id',
                'reservation_manual_purchases_purchase_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reservation_manual_purchases');
    }
};
