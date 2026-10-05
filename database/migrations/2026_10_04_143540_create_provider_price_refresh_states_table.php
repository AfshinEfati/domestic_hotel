<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('provider_price_refresh_states', function (Blueprint $table): void {
            $table->id()->comment('Primary key.');
            $table->foreignId('provider_id')
                ->comment('Provider whose independent refresh state is tracked.')
                ->constrained('providers')
                ->restrictOnDelete();
            $table->foreignId('accommodation_id')
                ->comment('Local accommodation whose provider refresh state is tracked.')
                ->constrained('accommodations')
                ->cascadeOnDelete();
            $table->unsignedBigInteger('schedule_id')->nullable()->comment('Shared SSP schedule identifier used as the refresh policy source.');
            $table->timestamp('next_run_at')->nullable()->comment('Next provider-specific rate and inventory refresh time.');
            $table->timestamp('last_request_at')->nullable()->comment('Most recent provider availability request start time.');
            $table->timestamp('last_success_at')->nullable()->comment('Most recent successful provider HTTP availability response time.');
            $table->timestamp('last_persisted_at')->nullable()->comment('Most recent successful local persistence time.');
            $table->string('last_error', 1000)->nullable()->comment('Most recent provider refresh error summary.');
            $table->timestamp('created_at')->nullable()->comment('Row creation timestamp.');
            $table->timestamp('updated_at')->nullable()->comment('Row last update timestamp.');

            $table->unique(['provider_id', 'accommodation_id'], 'provider_price_refresh_states_unique');
            $table->index(['provider_id', 'next_run_at'], 'provider_price_refresh_states_due_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('provider_price_refresh_states');
    }
};
