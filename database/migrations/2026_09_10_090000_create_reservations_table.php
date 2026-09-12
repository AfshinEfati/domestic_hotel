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

            $table->unsignedTinyInteger('status')
                ->index()
                ->comment('Current reservation status');

            $table->date('check_in')
                ->comment('Reservation check-in date');

            $table->date('check_out')
                ->comment('Reservation check-out date');

            $table->unsignedBigInteger('sale_amount')
                ->comment('Final amount sold to the end user in IRR');

            $table->string('booker_first_name', 100)
                ->comment('Booker first name');

            $table->string('booker_last_name', 100)
                ->comment('Booker last name');

            $table->string('booker_mobile', 32)
                ->index()
                ->comment('Booker mobile number');

            $table->string('booker_email')
                ->nullable()
                ->comment('Booker email address');

            $table->string('acc_code', 64)
                ->nullable()
                ->index()
                ->comment('Sales agent code in the accounting system');

            $table->timestamps();

            $table->index(
                ['check_in', 'check_out'],
                'reservations_stay_dates_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reservations');
    }
};
