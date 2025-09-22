<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('rate_plan_provider_maps', function (Blueprint $t) {
            $t->id();
            $t->foreignId('rate_plan_id')->constrained()->cascadeOnDelete();
            $t->foreignId('provider_id')->constrained()->cascadeOnDelete();
            $t->string('provider_rate_plan_id', 64);
            $t->string('fa_name', 200)->nullable();
            $t->string('en_name', 200)->nullable();
            $t->timestamps();

            $t->unique(['provider_id', 'provider_rate_plan_id'], 'uniq_provider_rate_plan');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rate_plan_provider_maps');
    }
};
