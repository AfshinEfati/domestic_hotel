<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('provider_stay_packages', function (Blueprint $table): void {
            $table->id()->comment('Primary key.');
            $table->foreignId('accommodation_provider_map_id')
                ->comment('Provider-specific accommodation mapping owning this stay package.')
                ->constrained('accommodation_provider_maps')
                ->cascadeOnDelete();
            $table->foreignId('provider_id')
                ->comment('Provider that supplied this stay package.')
                ->constrained('providers')
                ->restrictOnDelete();
            $table->foreignId('room_type_provider_map_id')
                ->nullable()
                ->comment('Mapped provider room when the package is room-specific.')
                ->constrained('room_type_provider_maps')
                ->cascadeOnDelete();
            $table->string('provider_room_type_id', 64)->nullable()->comment('Provider room identifier associated with this package.');
            $table->string('title', 500)->nullable()->comment('Provider stay package title.');
            $table->date('check_in')->comment('Inclusive package check-in date.');
            $table->date('check_out')->comment('Exclusive package check-out date.');
            $table->boolean('is_active')->default(true)->comment('Whether this provider package is active in the latest overlapping provider refresh window.');
            $table->timestamp('last_seen_at')->nullable()->comment('Most recent time this package was observed in a provider availability response.');
            $table->char('package_key', 64)->comment('Stable SHA-256 key for de-duplicating provider stay packages.');
            $table->timestamp('created_at')->nullable()->comment('Row creation timestamp.');
            $table->timestamp('updated_at')->nullable()->comment('Row last update timestamp.');

            $table->unique(
                ['accommodation_provider_map_id', 'package_key'],
                'provider_stay_packages_map_key_unique'
            );
            $table->index(
                ['provider_id', 'check_in', 'check_out'],
                'provider_stay_packages_provider_dates_idx'
            );
            $table->index(
                ['provider_id', 'is_active', 'check_in', 'check_out'],
                'provider_stay_packages_active_dates_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('provider_stay_packages');
    }
};
