<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hotel_provider_refresh_states', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('shared_schedule_id')->comment('Source hotel_price_refresh_schedules row ID from shared SSP.');
            $table->foreignId('accommodation_id')->constrained('accommodations')->cascadeOnDelete()->comment('Canonical local accommodation ID.');
            $table->foreignId('provider_id')->constrained('providers')->cascadeOnDelete()->comment('Provider whose refresh state is tracked.');
            $table->unsignedBigInteger('accommodation_provider_map_id')->nullable()->comment('Provider map used for this refresh cycle.');
            $table->string('cycle_key', 64)->comment('Stable key for one shared due cycle.');
            $table->timestamp('source_due_at')->nullable()->comment('Shared next_gds_run_at value that opened this cycle.');
            $table->unsignedTinyInteger('status')->default(1)->comment('Refresh state code: pending/queued/processing/retry/done/attempted.');
            $table->string('outcome', 64)->nullable()->comment('Terminal or retry reason code without verbose provider payloads.');
            $table->unsignedSmallInteger('attempts')->default(0)->comment('Number of worker executions started for this provider cycle.');
            $table->timestamp('next_attempt_at')->nullable()->comment('Earliest retry time for retryable failures.');
            $table->timestamp('queued_at')->nullable()->comment('Last time this state was claimed for queue dispatch.');
            $table->timestamp('started_at')->nullable()->comment('Last worker start time.');
            $table->timestamp('last_attempt_at')->nullable()->comment('Last actual refresh attempt time.');
            $table->timestamp('last_success_at')->nullable()->comment('Last successful provider refresh time for this cycle.');
            $table->timestamp('completed_at')->nullable()->comment('Terminal completion time for done/attempted states.');
            $table->timestamp('lease_expires_at')->nullable()->comment('Recovery deadline for queued/processing workers that disappear.');
            $table->timestamps();

            $table->unique(['cycle_key', 'provider_id'], 'hotel_provider_refresh_cycle_provider_unique');
            $table->index(['provider_id', 'status', 'next_attempt_at'], 'hotel_provider_refresh_provider_status_idx');
            $table->index(['cycle_key', 'status'], 'hotel_provider_refresh_cycle_status_idx');
            $table->index(['shared_schedule_id', 'accommodation_id'], 'hotel_provider_refresh_schedule_hotel_idx');
            $table->index('accommodation_provider_map_id', 'hotel_provider_refresh_map_idx');

            $table->foreign('accommodation_provider_map_id', 'hotel_provider_refresh_map_fk')
                ->references('id')
                ->on('accommodation_provider_maps')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hotel_provider_refresh_states');
    }
};
