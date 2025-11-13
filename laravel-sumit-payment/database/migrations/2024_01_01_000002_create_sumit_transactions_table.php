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
        Schema::create(config('sumit-payment.tables.transactions', 'sumit_transactions'), function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('order_id', 100)->nullable()->index();
            $table->string('transaction_id', 255)->nullable()->unique();
            $table->string('payment_method', 50);
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('ILS');
            $table->enum('status', ['pending', 'processing', 'completed', 'failed', 'refunded', 'cancelled'])->default('pending')->index();
            $table->integer('payments_count')->default(1);
            $table->text('description')->nullable();
            $table->string('document_id', 255)->nullable();
            $table->string('document_type', 50)->nullable();
            $table->string('customer_id', 255)->nullable();
            $table->string('authorization_number', 255)->nullable();
            $table->string('last_four_digits', 4)->nullable();
            $table->boolean('is_subscription')->default(false);
            $table->boolean('is_donation')->default(false);
            $table->json('metadata')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('status');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists(config('sumit-payment.tables.transactions', 'sumit_transactions'));
    }
};
