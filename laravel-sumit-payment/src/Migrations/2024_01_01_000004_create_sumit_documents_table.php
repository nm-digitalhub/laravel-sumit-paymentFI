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
        Schema::create(config('sumit-payment.tables.documents', 'sumit_documents'), function (Blueprint $table) {
            $table->id();
            $table->string('document_id', 100)->unique();
            $table->string('order_id', 100)->index();
            $table->string('customer_id', 100)->nullable();
            $table->string('type', 50); // invoice, receipt, order, credit, donation_receipt
            $table->string('language', 20)->nullable();
            $table->string('currency', 3);
            $table->decimal('total_amount', 10, 2);
            $table->decimal('vat_rate', 5, 2)->nullable();
            $table->boolean('vat_included')->default(true);
            $table->boolean('is_draft')->default(false);
            $table->boolean('sent_by_email')->default(false);
            $table->text('description')->nullable();
            $table->json('items')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['type', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists(config('sumit-payment.tables.documents', 'sumit_documents'));
    }
};
