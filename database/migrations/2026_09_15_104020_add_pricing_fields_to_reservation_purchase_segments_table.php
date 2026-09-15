<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reservation_purchase_segments', function (Blueprint $table) {
            $table->unsignedBigInteger('nightly_extra_bed_purchase_amount')
                ->nullable()
                ->after('nightly_purchase_amount')
                ->comment('Procurement amount per night for an extra bed in IRR');

            $table->unsignedBigInteger('nightly_child_purchase_amount')
                ->nullable()
                ->after('nightly_extra_bed_purchase_amount')
                ->comment('Procurement amount per night for a child in IRR');
        });
    }

    public function down(): void
    {
        Schema::table('reservation_purchase_segments', function (Blueprint $table) {
            $table->dropColumn([
                'nightly_extra_bed_purchase_amount',
                'nightly_child_purchase_amount',
            ]);
        });
    }
};
