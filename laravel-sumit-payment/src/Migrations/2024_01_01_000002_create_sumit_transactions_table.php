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
            $table->string('order_id', 100)->index();
            $table->string('payment_id', 100)->unique();
            $table->string('auth_number', 50)->nullable();
            $table->decimal('amount', 10, 2);
            $table->decimal('first_payment_amount', 10, 2)->nullable();
            $table->decimal('non_first_payment_amount', 10, 2)->nullable();
            $table->string('currency', 3);
            $table->integer('payments_count')->default(1);
            $table->string('status', 50); // pending, completed, failed, refunded
            $table->text('status_description')->nullable();
            $table->boolean('valid_payment')->default(false);
            $table->unsignedBigInteger('payment_token_id')->nullable();
            $table->string('card_last_four', 4)->nullable();
            $table->string('card_expiry_month', 2)->nullable();
            $table->string('card_expiry_year', 4)->nullable();
            $table->string('document_id', 100)->nullable();
            $table->string('customer_id', 100)->nullable();
            $table->boolean('is_subscription')->default(false);
            $table->boolean('is_test')->default(false);
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'created_at']);
            $table->foreign('payment_token_id')
                ->references('id')
                ->on(config('sumit-payment.tables.payment_tokens', 'sumit_payment_tokens'))
                ->onDelete('set null');
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
