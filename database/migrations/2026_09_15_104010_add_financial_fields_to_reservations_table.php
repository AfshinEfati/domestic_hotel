<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->unsignedBigInteger('agency_id')
                ->after('id')
                ->index()
                ->comment('Required agency identifier supplied by the upstream system');

            $table->unsignedBigInteger('tax_amount')
                ->default(0)
                ->after('sale_amount')
                ->comment('Reservation tax amount in IRR; currently defaults to zero');

            $table->bigInteger('commission_amount')
                ->nullable()
                ->after('tax_amount')
                ->comment('Sale amount minus actual purchase amount in IRR; may be negative');
        });
    }

    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->dropIndex(['agency_id']);
            $table->dropColumn([
                'agency_id',
                'tax_amount',
                'commission_amount',
            ]);
        });
    }
};
