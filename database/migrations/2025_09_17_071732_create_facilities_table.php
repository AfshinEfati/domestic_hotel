<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('facilities', function (Blueprint $t) {
            $t->id();
            $t->foreignId('facility_group_id')->constrained()->cascadeOnDelete();
            $t->string('fa_name', 160);
            $t->string('en_name', 160)->nullable();
            $t->timestamps();
        });
        Schema::create('accommodation_facility', function (Blueprint $t) {
            $t->id();
            $t->foreignId('accommodation_id')->constrained()->cascadeOnDelete();
            $t->foreignId('facility_id')->constrained()->cascadeOnDelete();
            $t->string('description', 500)->nullable();
            $t->timestamps();

            $t->unique(['accommodation_id', 'facility_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('facilities');
    }
};
