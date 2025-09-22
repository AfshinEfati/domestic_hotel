<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('room_types', function (Blueprint $t) {
            $t->id();
            $t->foreignId('accommodation_id')->constrained()->cascadeOnDelete();
            $t->string('fa_name', 200);
            $t->string('en_name', 200)->nullable();
            $t->unsignedSmallInteger('capacity')->nullable();
            $t->unsignedSmallInteger('extra_capacity')->nullable();
            $t->unsignedSmallInteger('single_bed_count')->nullable();
            $t->unsignedSmallInteger('double_bed_count')->nullable();
            $t->unsignedSmallInteger('sofa_bed_count')->nullable();
            $t->boolean('out_of_service')->default(false);
            $t->timestamps();

            $t->unique(['accommodation_id', 'fa_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('room_types');
    }
};
