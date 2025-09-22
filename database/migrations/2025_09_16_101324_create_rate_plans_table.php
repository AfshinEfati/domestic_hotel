<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('rate_plans', function (Blueprint $t) {
            $t->id();
            $t->foreignId('accommodation_id')->constrained()->cascadeOnDelete();
            $t->string('fa_name', 200);
            $t->string('en_name', 200)->nullable();
            $t->enum('meal_type', ['breakfast','half_board','full_board'])->nullable();
            $t->enum('food_board_type', ['limit_options','full_options'])->nullable();
            $t->boolean('cancelable')->default(true);
            $t->unsignedSmallInteger('sleeps')->nullable();
            $t->unsignedSmallInteger('min_stay')->nullable();
            $t->unsignedSmallInteger('max_stay')->nullable();
            $t->json('facilities')->nullable();
            $t->timestamps();

            $t->unique(['accommodation_id', 'fa_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rate_plans');
    }
};
