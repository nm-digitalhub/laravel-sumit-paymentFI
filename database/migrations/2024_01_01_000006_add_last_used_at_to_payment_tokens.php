<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $tableName = config('sumit-payment.tables.payment_tokens', 'sumit_payment_tokens');
        
        Schema::table($tableName, function (Blueprint $table) {
            $table->timestamp('last_used_at')->nullable()->after('expires_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tableName = config('sumit-payment.tables.payment_tokens', 'sumit_payment_tokens');
        
        Schema::table($tableName, function (Blueprint $table) {
            $table->dropColumn('last_used_at');
        });
    }
};
