<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('rule_categories', function (Blueprint $table) {
            $table->id();
            $table->string('code', 80)->unique();
            $table->string('name', 160);
            $table->string('name_en', 160)->nullable();
            $table->string('name_ar', 160)->nullable();
            $table->timestamps();
        });

        Schema::create('rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hotel_id')->constrained('accommodations')->cascadeOnDelete();
            $table->foreignId('rule_category_id')->nullable()->constrained('rule_categories')->nullOnDelete();
            $table->string('provider_rule_id', 64);
            $table->unsignedInteger('rule_id')->nullable();
            $table->string('type', 50)->nullable();
            $table->string('name', 255)->nullable();
            $table->string('name_ar', 255)->nullable();
            $table->string('name_en', 255)->nullable();
            $table->json('conditions')->nullable();
            $table->string('room_type_id', 64)->nullable();
            $table->string('rate_plan_id', 64)->nullable();
            $table->text('description')->nullable();
            $table->text('description_ar')->nullable();
            $table->text('description_en')->nullable();
            $table->boolean('status')->default(true);
            $table->timestamps();

            $table->unique(['hotel_id', 'provider_rule_id']);
            $table->index('rule_id');
            $table->index('type');
        });

        Schema::create('hotel_child_policy', function (Blueprint $table) {
            $table->id();
            $table->foreignId('accommodation_id')->constrained('accommodations')->cascadeOnDelete()->unique();
            $table->unsignedTinyInteger('max_infant_age')->default(0);
            $table->unsignedTinyInteger('max_child_age')->default(0);
            $table->enum('infant_when_disabled', ['as_child', 'as_adult'])->default('as_child');
            $table->enum('child_when_disabled', ['as_adult'])->default('as_adult');
            $table->enum('infant_service_condition', ['any', 'no_service', 'with_service'])->default('any');
            $table->enum('child_service_condition', ['any', 'no_service', 'with_service'])->default('any');
            $table->unsignedTinyInteger('max_children_covered')->nullable();
            $table->unsignedTinyInteger('max_infants_covered')->nullable();
            $table->enum('infant_pricing_type', ['adult', 'free', 'half', 'percent', 'fixed'])->default('adult');
            $table->unsignedSmallInteger('infant_pricing_value')->nullable();
            $table->enum('child_pricing_type', ['adult', 'free', 'half', 'percent', 'fixed'])->default('adult');
            $table->unsignedSmallInteger('child_pricing_value')->nullable();
            $table->text('description')->nullable();
            $table->boolean('status')->default(true);
            $table->timestamps();

            $table->index(['max_infant_age']);
            $table->index(['max_child_age']);
            $table->index(['infant_service_condition']);
            $table->index(['child_service_condition']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hotel_child_policy');
        Schema::dropIfExists('rules');
        Schema::dropIfExists('rule_categories');
    }
};
