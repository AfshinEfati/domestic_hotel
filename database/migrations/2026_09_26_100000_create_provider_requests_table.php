<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('provider_requests', function (Blueprint $table) {
            $table->id();

            $table->foreignId('provider_id')
                ->constrained('providers')
                ->cascadeOnDelete()
                ->comment('Provider that received the HTTP request');

            $table->foreignId('reservation_id')
                ->nullable()
                ->constrained('reservations')
                ->nullOnDelete()
                ->comment('Related reservation when this provider call belongs to a reservation flow');

            $table->string('handler_class', 512)
                ->nullable()
                ->comment('Application class responsible for the provider call');

            $table->string('handler_method', 191)
                ->nullable()
                ->comment('Application method responsible for the provider call');

            $table->string('http_method', 16)
                ->comment('HTTP method sent to the provider');

            $table->string('url', 2048)
                ->comment('Provider endpoint URL including query string');

            $table->longText('request_body')
                ->nullable()
                ->comment('Raw request body sent to the provider');

            $table->longText('response_body')
                ->nullable()
                ->comment('Raw response body returned by the provider');

            $table->unsignedSmallInteger('http_status')
                ->nullable()
                ->comment('HTTP response status code returned by the provider');

            $table->unsignedTinyInteger('status')
                ->index()
                ->comment('Internal provider request execution status');

            $table->unsignedSmallInteger('attempt')
                ->default(1)
                ->comment('Attempt number for this provider operation');

            $table->string('exception_class', 512)
                ->nullable()
                ->comment('Exception class when no normal provider response is available');

            $table->text('error_message')
                ->nullable()
                ->comment('Failure message when the provider request cannot complete normally');

            $table->timestamp('started_at')
                ->comment('Time when the provider HTTP request started');

            $table->timestamp('finished_at')
                ->nullable()
                ->comment('Time when the provider HTTP request finished');

            $table->unsignedInteger('duration_ms')
                ->nullable()
                ->comment('Provider HTTP request duration in milliseconds');

            $table->timestamp('expires_at')
                ->index()
                ->comment('Retention deadline; provider request logs are kept for 15 days');

            $table->timestamps();

            $table->index(['provider_id', 'created_at'], 'provider_requests_provider_timeline_idx');
            $table->index(['reservation_id', 'created_at'], 'provider_requests_reservation_timeline_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('provider_requests');
    }
};
