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
            $table->boolean('is_online')
                ->default(false)
                ->after('is_active')
                ->index()
                ->comment('Indicates whether the provider supports automatic online operations');
        });

        DB::table('providers')->update([
            'is_online' => true,
        ]);
    }

    public function down(): void
    {
        Schema::table('providers', function (Blueprint $table) {
            $table->dropIndex(['is_online']);
            $table->dropColumn('is_online');
        });
    }
};
