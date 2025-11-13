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
        $tableName = config('sumit-payment.tables.transactions', 'sumit_transactions');
        
        Schema::table($tableName, function (Blueprint $table) {
            $table->string('type')->default('payment')->after('status');
            $table->unsignedBigInteger('payment_token_id')->nullable()->after('is_donation');
            $table->decimal('refund_amount', 10, 2)->nullable()->after('payment_token_id');
            $table->string('refund_status')->nullable()->after('refund_amount');
            
            // Add index for better performance
            $table->index('type');
            $table->index('payment_token_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tableName = config('sumit-payment.tables.transactions', 'sumit_transactions');
        
        Schema::table($tableName, function (Blueprint $table) {
            $table->dropIndex(['type']);
            $table->dropIndex(['payment_token_id']);
            $table->dropColumn(['type', 'payment_token_id', 'refund_amount', 'refund_status']);
        });
    }
};
