<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('accommodation_provider_details', function (Blueprint $table): void {
            $table->unsignedSmallInteger('calendar_window_days')
                ->nullable()
                ->after('free_transfers')
                ->comment('Provider-specific maximum calendar request window in days learned or configured for this accommodation.');
        });
    }

    public function down(): void
    {
        Schema::table('accommodation_provider_details', function (Blueprint $table): void {
            $table->dropColumn('calendar_window_days');
        });
    }
};
