<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reservation_hotels', function (Blueprint $table): void {
            // A ticket must persist even if the selected calendar was pruned.
            // Retain room calendar IDs and guest snapshots until the failed check is recorded.
            $table->foreignId('accommodation_id')
                ->nullable()
                ->comment('Accommodation selected or proposed for the reservation')
                ->change();
        });
    }

    public function down(): void
    {
        // Reconcile unresolved tickets before reverting to NOT NULL.
        Schema::table('reservation_hotels', function (Blueprint $table): void {
            $table->foreignId('accommodation_id')
                ->nullable(false)
                ->comment('Accommodation selected or proposed for the reservation')
                ->change();
        });
    }
};
