<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('room_type_names', function (Blueprint $table) {
            $table->id();
            $table->string('fa_name')->unique();
            $table->string('en_name')->nullable();
            $table->timestamps();
        });

        Schema::table('room_types', function (Blueprint $table) {
            $table->foreignId('room_type_name_id')->after('accommodation_id')->nullable()->constrained('room_type_names')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('room_types', function (Blueprint $table) {
            $table->dropForeign(['room_type_name_id']);
            $table->dropColumn('room_type_name_id');
        });

        Schema::dropIfExists('room_type_names');
    }
};
