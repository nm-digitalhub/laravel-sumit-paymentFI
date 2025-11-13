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
        Schema::create('officeguy_payments', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_id', 255)->unique()->nullable();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->unsignedBigInteger('order_id')->nullable()->index();
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('ILS');
            $table->string('status', 50); // success, failed, pending, authorized, captured
            $table->string('payment_method', 50)->nullable();
            $table->unsignedBigInteger('token_id')->nullable();
            $table->text('request_data')->nullable();
            $table->text('response_data')->nullable();
            $table->text('error_message')->nullable();
            $table->string('document_number', 100)->nullable();
            $table->string('document_type', 50)->nullable(); // invoice, receipt, donation_receipt
            $table->boolean('is_subscription_payment')->default(false);
            $table->integer('payments_count')->default(1);
            $table->boolean('auto_capture')->default(true);
            $table->decimal('authorize_amount', 10, 2)->nullable();
            $table->timestamp('authorized_at')->nullable();
            $table->timestamp('captured_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Foreign keys (optional)
            // $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
            // $table->foreign('token_id')->references('id')->on('officeguy_payment_tokens')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('officeguy_payments');
    }
};
