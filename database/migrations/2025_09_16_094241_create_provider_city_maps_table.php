<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('provider_city_maps', function (Blueprint $t) {
            $t->id();
            $t->foreignId('city_id')->constrained()->cascadeOnDelete();
            $t->foreignId('provider_id')->constrained()->cascadeOnDelete();
            $t->string('provider_city_id', 64);
            $t->string('fa_name', 160)->nullable();
            $t->string('en_name', 160)->nullable();
            $t->timestamps();

            $t->unique(['provider_id', 'provider_city_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('provider_city_maps');
    }
};
