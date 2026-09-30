<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('room_type_provider_maps', function (Blueprint $table): void {
            $table->foreignId('accommodation_provider_map_id')
                ->nullable()
                ->after('provider_id')
                ->constrained('accommodation_provider_maps')
                ->cascadeOnDelete();
        });

        Schema::table('rate_plan_provider_maps', function (Blueprint $table): void {
            $table->foreignId('accommodation_provider_map_id')
                ->nullable()
                ->after('provider_id')
                ->constrained('accommodation_provider_maps')
                ->cascadeOnDelete();
        });

        $this->backfillRoomTypeMaps();
        $this->backfillRatePlanMaps();

        Schema::table('room_type_provider_maps', function (Blueprint $table): void {
            $table->dropUnique('uniq_provider_room_type');
            $table->unique(
                ['accommodation_provider_map_id', 'provider_room_type_id'],
                'uniq_accommodation_provider_room_type'
            );
        });

        Schema::table('rate_plan_provider_maps', function (Blueprint $table): void {
            $table->dropUnique('uniq_provider_rate_plan');
            $table->unique(
                ['accommodation_provider_map_id', 'provider_rate_plan_id'],
                'uniq_accommodation_provider_rate_plan'
            );
        });

        Schema::table('room_type_provider_maps', function (Blueprint $table): void {
            $table->unsignedBigInteger('accommodation_provider_map_id')->nullable(false)->change();
        });

        Schema::table('rate_plan_provider_maps', function (Blueprint $table): void {
            $table->unsignedBigInteger('accommodation_provider_map_id')->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('room_type_provider_maps', function (Blueprint $table): void {
            $table->dropUnique('uniq_accommodation_provider_room_type');
            $table->dropForeign(['accommodation_provider_map_id']);
            $table->dropColumn('accommodation_provider_map_id');
            $table->unique(['provider_id', 'provider_room_type_id'], 'uniq_provider_room_type');
        });

        Schema::table('rate_plan_provider_maps', function (Blueprint $table): void {
            $table->dropUnique('uniq_accommodation_provider_rate_plan');
            $table->dropForeign(['accommodation_provider_map_id']);
            $table->dropColumn('accommodation_provider_map_id');
            $table->unique(['provider_id', 'provider_rate_plan_id'], 'uniq_provider_rate_plan');
        });
    }

    private function backfillRoomTypeMaps(): void
    {
        DB::table('room_type_provider_maps')
            ->orderBy('id')
            ->chunkById(500, function ($rows): void {
                $roomIds = $rows->pluck('room_type_id')->map(fn ($id) => (int) $id)->all();
                $accommodationByRoom = DB::table('room_types')
                    ->whereIn('id', $roomIds)
                    ->pluck('accommodation_id', 'id');

                foreach ($rows as $row) {
                    $accommodationId = $accommodationByRoom[(int) $row->room_type_id] ?? null;
                    if ($accommodationId === null) {
                        throw new RuntimeException(
                            "Cannot backfill room_type_provider_maps #{$row->id}: room type is missing."
                        );
                    }

                    $mapId = DB::table('accommodation_provider_maps')
                        ->where('provider_id', (int) $row->provider_id)
                        ->where('accommodation_id', (int) $accommodationId)
                        ->orderBy('id')
                        ->value('id');

                    if ($mapId === null) {
                        throw new RuntimeException(
                            "Cannot backfill room_type_provider_maps #{$row->id}: accommodation provider map is missing."
                        );
                    }

                    DB::table('room_type_provider_maps')
                        ->where('id', (int) $row->id)
                        ->update(['accommodation_provider_map_id' => (int) $mapId]);
                }
            });
    }

    private function backfillRatePlanMaps(): void
    {
        DB::table('rate_plan_provider_maps')
            ->orderBy('id')
            ->chunkById(500, function ($rows): void {
                $ratePlanIds = $rows->pluck('rate_plan_id')->map(fn ($id) => (int) $id)->all();
                $accommodationByRatePlan = DB::table('rate_plans')
                    ->whereIn('id', $ratePlanIds)
                    ->pluck('accommodation_id', 'id');

                foreach ($rows as $row) {
                    $accommodationId = $accommodationByRatePlan[(int) $row->rate_plan_id] ?? null;
                    if ($accommodationId === null) {
                        throw new RuntimeException(
                            "Cannot backfill rate_plan_provider_maps #{$row->id}: rate plan is missing."
                        );
                    }

                    $mapId = DB::table('accommodation_provider_maps')
                        ->where('provider_id', (int) $row->provider_id)
                        ->where('accommodation_id', (int) $accommodationId)
                        ->orderBy('id')
                        ->value('id');

                    if ($mapId === null) {
                        throw new RuntimeException(
                            "Cannot backfill rate_plan_provider_maps #{$row->id}: accommodation provider map is missing."
                        );
                    }

                    DB::table('rate_plan_provider_maps')
                        ->where('id', (int) $row->id)
                        ->update(['accommodation_provider_map_id' => (int) $mapId]);
                }
            });
    }
};
