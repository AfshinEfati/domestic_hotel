<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reservation_provider_operations', function (Blueprint $table) {
            $table->id();

            $table->foreignId('reservation_purchase_id')
                ->comment('Reservation purchase associated with this provider operation')
                ->constrained('reservation_purchases')
                ->cascadeOnDelete();

            $table->unsignedTinyInteger('operation')
                ->index()
                ->comment('Provider operation type such as reserve, create, confirm, status, or cancel');

            $table->unsignedTinyInteger('status')
                ->index()
                ->comment('Execution status of the provider operation');

            $table->unsignedSmallInteger('attempt')
                ->default(1)
                ->comment('Attempt number for the same provider operation');

            $table->string('handler_class', 512)
                ->comment('Fully qualified class name that executed the provider operation');

            $table->string('handler_method', 191)
                ->comment('Method name that executed the provider operation');

            $table->string('url', 2048)
                ->comment('Provider endpoint URL used for the request');

            $table->json('request_headers')
                ->nullable()
                ->comment('Sanitized request headers sent to the provider');

            $table->longText('request_body')
                ->nullable()
                ->comment('Request body sent to the provider');

            $table->unsignedSmallInteger('http_status')
                ->nullable()
                ->comment('HTTP response status code returned by the provider');

            $table->json('response_headers')
                ->nullable()
                ->comment('Sanitized response headers returned by the provider');

            $table->longText('response_body')
                ->nullable()
                ->comment('Response body returned by the provider');

            $table->string('idempotency_key', 191)
                ->nullable()
                ->index()
                ->comment('Idempotency key used for safely retrying provider requests');

            $table->string('exception_class', 512)
                ->nullable()
                ->comment('Fully qualified exception class when the operation fails');

            $table->text('error_message')
                ->nullable()
                ->comment('Exception or error message when the operation fails');

            $table->unsignedInteger('error_line')
                ->nullable()
                ->comment('Source line where the exception was thrown');

            $table->timestamp('started_at')
                ->comment('Time when the provider operation started');

            $table->timestamp('finished_at')
                ->nullable()
                ->comment('Time when the provider operation finished');

            $table->unsignedInteger('duration_ms')
                ->nullable()
                ->comment('Provider operation duration in milliseconds');

            $table->timestamps();

            $table->index(
                ['reservation_purchase_id', 'created_at'],
                'reservation_provider_operations_purchase_timeline_idx'
            );

            $table->index(
                ['reservation_purchase_id', 'operation', 'attempt'],
                'reservation_provider_operations_attempt_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reservation_provider_operations');
    }
};
