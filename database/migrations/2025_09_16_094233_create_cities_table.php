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
        Schema::create('cities', function (Blueprint $t) {
            $t->id();
            $t->foreignId('country_id')->constrained()->cascadeOnDelete();
            $t->foreignId('state_id')->nullable()->constrained()->nullOnDelete();
            $t->string('fa_name', 160);
            $t->string('en_name', 160)->nullable();
            $t->decimal('lat', 10, 7)->nullable();
            $t->decimal('lng', 10, 7)->nullable();
            $t->string('osm_id', 64)->nullable()->index();
            $t->boolean('is_popular')->default(false);
            $t->boolean('is_active')->default(true);
            $t->timestamps();

            $t->unique(['country_id', 'fa_name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cities');
    }
};
