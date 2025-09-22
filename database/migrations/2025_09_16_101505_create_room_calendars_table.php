<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('room_calendars', function (Blueprint $t) {
            $t->id();
            $t->foreignId('accommodation_id')->constrained()->cascadeOnDelete();
            $t->foreignId('room_type_id')->constrained()->cascadeOnDelete();
            $t->foreignId('rate_plan_id')->constrained()->cascadeOnDelete();
            $t->date('day')->index();

            // قیمت‌ها
            $t->unsignedInteger('rack_rate')->nullable();
            $t->unsignedInteger('daily_rate')->nullable();
            $t->unsignedInteger('grs_rate')->nullable();

            // نرخ کودک و نفر اضافه
            $t->unsignedInteger('baby_cot_rack_rate')->nullable();
            $t->unsignedInteger('baby_cot_daily_rate')->nullable();
            $t->unsignedInteger('baby_cot_grs_rate')->nullable();
            $t->unsignedInteger('extend_bed_rack_rate')->nullable();
            $t->unsignedInteger('extend_bed_daily_rate')->nullable();
            $t->unsignedInteger('extend_bed_grs_rate')->nullable();

            // قوانین اقامتی
            $t->unsignedSmallInteger('min_stay')->nullable();
            $t->unsignedSmallInteger('max_stay')->nullable();
            $t->boolean('cta')->default(false); // close_to_arrival
            $t->boolean('ctd')->default(false); // close_to_departure
            $t->boolean('closed')->default(false);

            // موجودی
            $t->unsignedSmallInteger('inventory')->nullable();

            // شناسه تأمین‌کننده
            $t->foreignId('provider_id')->constrained()->cascadeOnDelete();
            $t->string('provider_property_id', 64)->nullable();
            $t->string('provider_room_type_id', 64)->nullable();
            $t->string('provider_rate_plan_id', 64)->nullable();

            $t->timestamps();

            $t->unique(
                ['room_type_id', 'rate_plan_id', 'day', 'provider_id'],
                'uniq_calendar_dim'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('room_calendars');
    }
};
