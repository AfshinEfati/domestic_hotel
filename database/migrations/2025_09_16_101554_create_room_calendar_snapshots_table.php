<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('room_calendar_snapshots', function (Blueprint $t) {
            $t->id();
            $t->foreignId('room_calendar_id')->constrained()->cascadeOnDelete();
            $t->foreignId('provider_id')->constrained()->cascadeOnDelete();
            $t->date('day')->index();
            $t->json('payload')->nullable(); // RAW provider response
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('room_calendar_snapshots');
    }
};
