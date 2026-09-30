<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        $this->ensureAccommodationMapColumns();

        $this->backfillRoomTypeMaps();
        $this->backfillRatePlanMaps();
        $this->deleteInvalidRoomCalendars();

        // MySQL may use the old composite unique indexes to satisfy the provider_id
        // foreign keys. Give each FK its own supporting index before dropping them.
        $this->ensureProviderSupportingIndex(
            'room_type_provider_maps',
            'uniq_provider_room_type',
            'idx_room_type_provider_maps_provider_id'
        );
        $this->ensureProviderSupportingIndex(
            'rate_plan_provider_maps',
            'uniq_provider_rate_plan',
            'idx_rate_plan_provider_maps_provider_id'
        );

        if ($this->indexExists('room_type_provider_maps', 'uniq_provider_room_type')) {
            Schema::table('room_type_provider_maps', function (Blueprint $table): void {
                $table->dropUnique('uniq_provider_room_type');
            });
        }

        if (!$this->indexExists('room_type_provider_maps', 'uniq_accommodation_provider_room_type')) {
            Schema::table('room_type_provider_maps', function (Blueprint $table): void {
                $table->unique(
                    ['accommodation_provider_map_id', 'provider_room_type_id'],
                    'uniq_accommodation_provider_room_type'
                );
            });
        }

        if ($this->indexExists('rate_plan_provider_maps', 'uniq_provider_rate_plan')) {
            Schema::table('rate_plan_provider_maps', function (Blueprint $table): void {
                $table->dropUnique('uniq_provider_rate_plan');
            });
        }

        if (!$this->indexExists('rate_plan_provider_maps', 'uniq_accommodation_provider_rate_plan')) {
            Schema::table('rate_plan_provider_maps', function (Blueprint $table): void {
                $table->unique(
                    ['accommodation_provider_map_id', 'provider_rate_plan_id'],
                    'uniq_accommodation_provider_rate_plan'
                );
            });
        }

        Schema::table('room_type_provider_maps', function (Blueprint $table): void {
            $table->unsignedBigInteger('accommodation_provider_map_id')->nullable(false)->change();
        });

        Schema::table('rate_plan_provider_maps', function (Blueprint $table): void {
            $table->unsignedBigInteger('accommodation_provider_map_id')->nullable(false)->change();
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('room_type_provider_maps', 'accommodation_provider_map_id')) {
            Schema::table('room_type_provider_maps', function (Blueprint $table): void {
                $table->dropForeign(['accommodation_provider_map_id']);
            });

            if ($this->indexExists('room_type_provider_maps', 'uniq_accommodation_provider_room_type')) {
                Schema::table('room_type_provider_maps', function (Blueprint $table): void {
                    $table->dropUnique('uniq_accommodation_provider_room_type');
                });
            }

            Schema::table('room_type_provider_maps', function (Blueprint $table): void {
                $table->dropColumn('accommodation_provider_map_id');
            });
        }

        if (!$this->indexExists('room_type_provider_maps', 'uniq_provider_room_type')) {
            Schema::table('room_type_provider_maps', function (Blueprint $table): void {
                $table->unique(['provider_id', 'provider_room_type_id'], 'uniq_provider_room_type');
            });
        }

        if ($this->indexExists('room_type_provider_maps', 'idx_room_type_provider_maps_provider_id')) {
            Schema::table('room_type_provider_maps', function (Blueprint $table): void {
                $table->dropIndex('idx_room_type_provider_maps_provider_id');
            });
        }

        if (Schema::hasColumn('rate_plan_provider_maps', 'accommodation_provider_map_id')) {
            Schema::table('rate_plan_provider_maps', function (Blueprint $table): void {
                $table->dropForeign(['accommodation_provider_map_id']);
            });

            if ($this->indexExists('rate_plan_provider_maps', 'uniq_accommodation_provider_rate_plan')) {
                Schema::table('rate_plan_provider_maps', function (Blueprint $table): void {
                    $table->dropUnique('uniq_accommodation_provider_rate_plan');
                });
            }

            Schema::table('rate_plan_provider_maps', function (Blueprint $table): void {
                $table->dropColumn('accommodation_provider_map_id');
            });
        }

        if (!$this->indexExists('rate_plan_provider_maps', 'uniq_provider_rate_plan')) {
            Schema::table('rate_plan_provider_maps', function (Blueprint $table): void {
                $table->unique(['provider_id', 'provider_rate_plan_id'], 'uniq_provider_rate_plan');
            });
        }

        if ($this->indexExists('rate_plan_provider_maps', 'idx_rate_plan_provider_maps_provider_id')) {
            Schema::table('rate_plan_provider_maps', function (Blueprint $table): void {
                $table->dropIndex('idx_rate_plan_provider_maps_provider_id');
            });
        }
    }

    private function ensureAccommodationMapColumns(): void
    {
        if (!Schema::hasColumn('room_type_provider_maps', 'accommodation_provider_map_id')) {
            Schema::table('room_type_provider_maps', function (Blueprint $table): void {
                $table->foreignId('accommodation_provider_map_id')
                    ->nullable()
                    ->after('provider_id')
                    ->constrained('accommodation_provider_maps')
                    ->cascadeOnDelete();
            });
        }

        if (!Schema::hasColumn('rate_plan_provider_maps', 'accommodation_provider_map_id')) {
            Schema::table('rate_plan_provider_maps', function (Blueprint $table): void {
                $table->foreignId('accommodation_provider_map_id')
                    ->nullable()
                    ->after('provider_id')
                    ->constrained('accommodation_provider_maps')
                    ->cascadeOnDelete();
            });
        }
    }

    private function ensureProviderSupportingIndex(string $table, string $oldUnique, string $indexName): void
    {
        if ($this->hasLeadingIndexExcept($table, 'provider_id', $oldUnique)) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($indexName): void {
            $blueprint->index('provider_id', $indexName);
        });
    }

    private function hasLeadingIndexExcept(string $table, string $column, string $excludedIndex): bool
    {
        return DB::table('information_schema.statistics')
            ->whereRaw('table_schema = DATABASE()')
            ->where('table_name', $table)
            ->where('seq_in_index', 1)
            ->where('column_name', $column)
            ->where('index_name', '!=', $excludedIndex)
            ->exists();
    }

    private function indexExists(string $table, string $indexName): bool
    {
        return DB::table('information_schema.statistics')
            ->whereRaw('table_schema = DATABASE()')
            ->where('table_name', $table)
            ->where('index_name', $indexName)
            ->exists();
    }

    private function deleteInvalidRoomCalendars(): void
    {
        do {
            $ids = DB::table('room_calendars as calendars')
                ->join('room_types as rooms', 'rooms.id', '=', 'calendars.room_type_id')
                ->join('rate_plans as plans', 'plans.id', '=', 'calendars.rate_plan_id')
                ->where(function ($query): void {
                    $query->whereColumn('calendars.accommodation_id', '!=', 'rooms.accommodation_id')
                        ->orWhereColumn('calendars.accommodation_id', '!=', 'plans.accommodation_id');
                })
                ->limit(500)
                ->pluck('calendars.id');

            if ($ids->isNotEmpty()) {
                DB::table('room_calendars')->whereIn('id', $ids)->delete();
            }
        } while ($ids->isNotEmpty());
    }

    private function backfillRoomTypeMaps(): void
    {
        DB::table('room_type_provider_maps')
            ->whereNull('accommodation_provider_map_id')
            ->orderBy('id')
            ->chunkById(500, function ($rows): void {
                $roomIds = $rows->pluck('room_type_id')->map(fn ($id) => (int) $id)->all();
                $accommodationByRoom = DB::table('room_types')
                    ->whereIn('id', $roomIds)
                    ->pluck('accommodation_id', 'id');

                foreach ($rows as $row) {
                    $accommodationId = $accommodationByRoom[(int) $row->room_type_id] ?? null;
                    if ($accommodationId === null) {
                        throw new \RuntimeException(
                            "Cannot backfill room_type_provider_maps #{$row->id}: room type is missing."
                        );
                    }

                    $mapId = DB::table('accommodation_provider_maps')
                        ->where('provider_id', (int) $row->provider_id)
                        ->where('accommodation_id', (int) $accommodationId)
                        ->orderBy('id')
                        ->value('id');

                    if ($mapId === null) {
                        throw new \RuntimeException(
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
            ->whereNull('accommodation_provider_map_id')
            ->orderBy('id')
            ->chunkById(500, function ($rows): void {
                $ratePlanIds = $rows->pluck('rate_plan_id')->map(fn ($id) => (int) $id)->all();
                $accommodationByRatePlan = DB::table('rate_plans')
                    ->whereIn('id', $ratePlanIds)
                    ->pluck('accommodation_id', 'id');

                foreach ($rows as $row) {
                    $accommodationId = $accommodationByRatePlan[(int) $row->rate_plan_id] ?? null;
                    if ($accommodationId === null) {
                        throw new \RuntimeException(
                            "Cannot backfill rate_plan_provider_maps #{$row->id}: rate plan is missing."
                        );
                    }

                    $mapId = DB::table('accommodation_provider_maps')
                        ->where('provider_id', (int) $row->provider_id)
                        ->where('accommodation_id', (int) $accommodationId)
                        ->orderBy('id')
                        ->value('id');

                    if ($mapId === null) {
                        throw new \RuntimeException(
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
