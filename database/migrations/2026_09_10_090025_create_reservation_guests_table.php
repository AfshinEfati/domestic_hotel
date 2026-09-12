<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reservation_guests', function (Blueprint $table) {
            $table->id();

            $table->foreignId('reservation_room_id')
                ->comment('Reservation room assigned to the guest')
                ->constrained('reservation_rooms')
                ->cascadeOnDelete();

            $table->unsignedTinyInteger('type')
                ->comment('Guest type such as adult, child, or infant');

            $table->string('first_name', 100)
                ->comment('Guest first name');

            $table->string('last_name', 100)
                ->comment('Guest last name');

            $table->unsignedTinyInteger('gender')
                ->nullable()
                ->comment('Guest gender');

            $table->date('birth_date')
                ->nullable()
                ->comment('Guest date of birth');

            $table->foreignId('country_id')
                ->nullable()
                ->comment('Guest nationality country')
                ->constrained('countries')
                ->restrictOnDelete();

            $table->string('national_id', 32)
                ->nullable()
                ->comment('Guest national identification number');

            $table->string('passport_number', 64)
                ->nullable()
                ->comment('Guest passport number');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reservation_guests');
    }
};
