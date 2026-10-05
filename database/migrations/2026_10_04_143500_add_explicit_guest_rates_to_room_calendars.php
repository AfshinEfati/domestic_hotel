<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('room_calendars', function (Blueprint $table): void {
            $table->unsignedBigInteger('child_daily_rate')
                ->nullable()
                ->after('baby_cot_grs_rate')
                ->comment('Explicit provider child price for this night in IRR; null means calculate from hotel child policy.');
            $table->unsignedBigInteger('infant_daily_rate')
                ->nullable()
                ->after('child_daily_rate')
                ->comment('Explicit provider infant price for this night in IRR; null means calculate from hotel child policy.');
        });
    }

    public function down(): void
    {
        Schema::table('room_calendars', function (Blueprint $table): void {
            $table->dropColumn(['child_daily_rate', 'infant_daily_rate']);
        });
    }
};
