<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reservation_purchases', function (Blueprint $table) {
            $table->unsignedTinyInteger('purchase_mode')
                ->default(1)
                ->comment('Purchase execution mode such as online or manual');

            $table->unsignedTinyInteger('manual_reason')
                ->nullable()
                ->comment('Reason why purchase entered manual processing');

            $table->foreignId('manual_rule_id')
                ->nullable()
                ->comment('Manual rule that caused this purchase mode')
                ->constrained('purchase_manual_rules')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('reservation_purchases', function (Blueprint $table) {
            $table->dropForeign(['manual_rule_id']);
            $table->dropColumn([
                'purchase_mode',
                'manual_reason',
                'manual_rule_id',
            ]);
        });
    }
};
