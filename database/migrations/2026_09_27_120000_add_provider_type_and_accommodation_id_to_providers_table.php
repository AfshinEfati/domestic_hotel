<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('providers', function (Blueprint $table) {
            $table->unsignedTinyInteger('provider_type')
                ->default(1)
                ->after('is_online')
                ->index()
                ->comment('Provider nature: 1=integration provider, 2=direct hotel provider');

            $table->foreignId('accommodation_id')
                ->nullable()
                ->after('provider_type')
                ->comment('Accommodation represented by this provider when provider_type=2')
                ->constrained('accommodations')
                ->nullOnDelete();

            $table->unique(
                'accommodation_id',
                'providers_accommodation_unique'
            );
        });

        DB::table('providers')
            ->where('code', 'like', 'hotel-%')
            ->orderBy('id')
            ->get()
            ->each(function (object $provider): void {
                $rawId = substr((string) $provider->code, strlen('hotel-'));

                if ($rawId === '' || !ctype_digit($rawId)) {
                    return;
                }

                $accommodationId = (int) $rawId;

                if (!DB::table('accommodations')->where('id', $accommodationId)->exists()) {
                    return;
                }

                DB::table('providers')
                    ->where('id', $provider->id)
                    ->update([
                        'provider_type' => 2,
                        'accommodation_id' => $accommodationId,
                    ]);
            });
    }

    public function down(): void
    {
        Schema::table('providers', function (Blueprint $table) {
            $table->dropUnique('providers_accommodation_unique');
            $table->dropConstrainedForeignId('accommodation_id');
            $table->dropIndex(['provider_type']);
            $table->dropColumn('provider_type');
        });
    }
};
