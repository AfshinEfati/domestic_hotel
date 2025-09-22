<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('providers', function (Blueprint $t) {
            $t->id();
            $t->string('fa_name', 120);
            $t->string('en_name', 120)->nullable();
            $t->string('class')->nullable(); // مثلا \App\Services\Providers\GrsProvider
            $t->string('code', 50)->unique(); // مثلا grs, iho, parto
            $t->json('config')->nullable();   // برای token, base_url و ...
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('providers');
    }
};
