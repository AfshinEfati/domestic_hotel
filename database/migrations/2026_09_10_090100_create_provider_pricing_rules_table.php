<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('provider_pricing_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('provider_id')
                ->unique()
                ->constrained('providers')
                ->cascadeOnDelete();
            $table->decimal('percentage', 8, 4)->default(5);
            $table->unsignedBigInteger('fixed_amount')->default(0);
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('provider_pricing_rules');
    }
};
