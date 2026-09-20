<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reservation_hotels', function (Blueprint $table): void {
            // A ticket must persist even if the offered calendar expired before Create.
            // Retain room calendar IDs and guest snapshots until the failed check is recorded.
            $table->foreignId('accommodation_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        // Unresolved tickets must be reconciled before reverting to NOT NULL.
        Schema::table('reservation_hotels', function (Blueprint $table): void {
            $table->foreignId('accommodation_id')->nullable(false)->change();
        });
    }
};
