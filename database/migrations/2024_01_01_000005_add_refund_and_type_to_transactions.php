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
        
        if (Schema::hasTable($tableName)) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                if (!Schema::hasColumn($tableName, 'type')) {
                    $table->string('type')->default('payment')->after('status');
                }
                if (!Schema::hasColumn($tableName, 'payment_token_id')) {
                    $table->unsignedBigInteger('payment_token_id')->nullable()->after('is_donation');
                }
                if (!Schema::hasColumn($tableName, 'refund_amount')) {
                    $table->decimal('refund_amount', 10, 2)->nullable()->after('payment_token_id');
                }
                if (!Schema::hasColumn($tableName, 'refund_status')) {
                    $table->string('refund_status')->nullable()->after('refund_amount');
                }
            });
            
            // Add indexes only if columns were added
            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                $sm = Schema::getConnection()->getDoctrineSchemaManager();
                $indexes = $sm->listTableIndexes($tableName);
                
                if (!isset($indexes['sumit_transactions_type_index'])) {
                    $table->index('type');
                }
                if (!isset($indexes['sumit_transactions_payment_token_id_index'])) {
                    $table->index('payment_token_id');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tableName = config('sumit-payment.tables.transactions', 'sumit_transactions');
        
        if (Schema::hasTable($tableName)) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                $sm = Schema::getConnection()->getDoctrineSchemaManager();
                $indexes = $sm->listTableIndexes($tableName);
                
                if (isset($indexes['sumit_transactions_type_index'])) {
                    $table->dropIndex(['type']);
                }
                if (isset($indexes['sumit_transactions_payment_token_id_index'])) {
                    $table->dropIndex(['payment_token_id']);
                }
                
                if (Schema::hasColumn($tableName, 'type')) {
                    $table->dropColumn('type');
                }
                if (Schema::hasColumn($tableName, 'payment_token_id')) {
                    $table->dropColumn('payment_token_id');
                }
                if (Schema::hasColumn($tableName, 'refund_amount')) {
                    $table->dropColumn('refund_amount');
                }
                if (Schema::hasColumn($tableName, 'refund_status')) {
                    $table->dropColumn('refund_status');
                }
            });
        }
    }
};
