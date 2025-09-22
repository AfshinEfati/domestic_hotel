<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('accommodations', function (Blueprint $t) {
            $t->id();
            $t->foreignId('city_id')->constrained()->cascadeOnDelete();
            $t->string('fa_name', 200);
            $t->string('en_name', 200)->nullable();
            $t->foreignId('accommodation_type_id')->constrained()->cascadeOnDelete();
            $t->unsignedTinyInteger('star')->nullable();
            $t->string('grade', 32)->nullable();
            $t->string('address', 500)->nullable();
            $t->decimal('lat', 10, 7)->nullable();
            $t->decimal('lng', 10, 7)->nullable();
            $t->boolean('is_active')->default(true);
            $t->timestamps();

            $t->unique(['city_id', 'fa_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accommodations');
    }
};
