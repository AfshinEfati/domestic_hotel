<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('room_type_provider_maps', function (Blueprint $table): void {
            $table->json('provider_metadata')
                ->nullable()
                ->after('provider_extra_capacity')
                ->comment('Provider-specific room metadata that has no canonical room column.');
        });

        Schema::table('rate_plan_provider_maps', function (Blueprint $table): void {
            $table->json('provider_metadata')
                ->nullable()
                ->after('provider_rate_plan_id')
                ->comment('Provider-specific rate-plan metadata that has no canonical rate-plan column.');
        });
    }

    public function down(): void
    {
        Schema::table('rate_plan_provider_maps', function (Blueprint $table): void {
            $table->dropColumn('provider_metadata');
        });

        Schema::table('room_type_provider_maps', function (Blueprint $table): void {
            $table->dropColumn('provider_metadata');
        });
    }
};
