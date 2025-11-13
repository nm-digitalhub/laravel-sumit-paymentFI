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
        Schema::create(config('sumit-payment.tables.customers', 'sumit_customers'), function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('sumit_customer_id', 255)->unique();
            $table->string('email', 255)->nullable();
            $table->string('phone', 50)->nullable();
            $table->string('name', 255)->nullable();
            $table->string('company_name', 255)->nullable();
            $table->string('tax_id', 100)->nullable();
            $table->text('address')->nullable();
            $table->string('city', 100)->nullable();
            $table->string('state', 100)->nullable();
            $table->string('country', 2)->default('IL');
            $table->string('zip_code', 20)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Foreign key constraint for users table (if exists)
            // $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists(config('sumit-payment.tables.customers', 'sumit_customers'));
    }
};
