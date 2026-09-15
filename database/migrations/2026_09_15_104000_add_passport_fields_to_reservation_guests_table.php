<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reservation_guests', function (Blueprint $table) {
            $table->foreignId('passport_issuer_country_id')
                ->nullable()
                ->after('passport_number')
                ->comment('Country that issued the guest passport')
                ->constrained('countries')
                ->restrictOnDelete();

            $table->date('passport_expiry_date')
                ->nullable()
                ->after('passport_issuer_country_id')
                ->comment('Guest passport expiration date');
        });
    }

    public function down(): void
    {
        Schema::table('reservation_guests', function (Blueprint $table) {
            $table->dropForeign(['passport_issuer_country_id']);
            $table->dropColumn([
                'passport_issuer_country_id',
                'passport_expiry_date',
            ]);
        });
    }
};
