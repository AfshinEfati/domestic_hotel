<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('accommodation_provider_details', function (Blueprint $table): void {
            $table->id()->comment('Primary key.');
            $table->foreignId('accommodation_provider_map_id')
                ->unique()
                ->comment('Provider-specific accommodation mapping owning these details.')
                ->constrained('accommodation_provider_maps')
                ->cascadeOnDelete();
            $table->string('accommodation_title', 500)->nullable()->comment('Provider-specific accommodation title when it differs from the canonical hotel name.');
            $table->text('description')->nullable()->comment('Provider-specific accommodation description.');
            $table->string('provider_url', 1000)->nullable()->comment('Provider-specific accommodation URL or URL key when supplied.');
            $table->boolean('is_marketplace')->nullable()->comment('Provider marketplace flag when supplied by the provider.');
            $table->string('check_in_time', 20)->nullable()->comment('Provider-declared hotel check-in time.');
            $table->string('check_out_time', 20)->nullable()->comment('Provider-declared hotel check-out time.');
            $table->text('cancellation_policy')->nullable()->comment('Provider-declared general cancellation policy text.');
            $table->boolean('foreigners_fee')->nullable()->comment('Whether the provider declares a foreign-guest fee policy for this accommodation.');
            $table->text('free_transfer_policy')->nullable()->comment('Provider-declared free-transfer policy text.');
            $table->json('free_transfers')->nullable()->comment('Provider-declared supported free-transfer types when supplied as structured data.');
            $table->timestamp('created_at')->nullable()->comment('Row creation timestamp.');
            $table->timestamp('updated_at')->nullable()->comment('Row last update timestamp.');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accommodation_provider_details');
    }
};
