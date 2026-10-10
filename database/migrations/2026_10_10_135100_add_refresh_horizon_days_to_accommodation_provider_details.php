<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('accommodation_provider_details', function (Blueprint $table): void {
            $table->unsignedSmallInteger('refresh_horizon_days')
                ->nullable()
                ->after('calendar_window_days')
                ->comment('Provider-specific maximum forward rate refresh horizon in days learned or configured for this accommodation.');
        });
    }

    public function down(): void
    {
        Schema::table('accommodation_provider_details', function (Blueprint $table): void {
            $table->dropColumn('refresh_horizon_days');
        });
    }
};
