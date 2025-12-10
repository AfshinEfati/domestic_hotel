<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('job_error_logs', function (Blueprint $table) {
            $table->id();

            // Job Information
            $table->string('job_id')->nullable();
            $table->string('job_class');
            $table->string('queue')->nullable();
            $table->unsignedInteger('attempts');
            $table->unsignedInteger('max_attempts');
            $table->json('job_payload');

            // Error Information
            $table->string('error_class');
            $table->text('error_message');
            $table->integer('error_code')->nullable();
            $table->string('error_file');
            $table->integer('error_line');
            $table->text('error_trace');

            // HTTP Context (for RequestException)
            $table->integer('http_status_code')->nullable();
            $table->text('http_response_body')->nullable();
            $table->string('http_request_url')->nullable();
            $table->string('http_request_method')->nullable();
            $table->json('http_response_headers')->nullable();

            // Additional Context
            $table->json('additional_context')->nullable();

            $table->timestamps();

            // Indexes
            $table->index('job_id');
            $table->index('job_class');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('job_error_logs');
    }
};
