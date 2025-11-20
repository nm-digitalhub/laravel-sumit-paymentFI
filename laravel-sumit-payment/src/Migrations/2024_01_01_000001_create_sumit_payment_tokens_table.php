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
        Schema::create(config('sumit-payment.tables.payment_tokens', 'sumit_payment_tokens'), function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();
            $table->string('token', 255);
            $table->string('card_type', 50)->nullable();
            $table->string('last_four', 4);
            $table->string('expiry_month', 2);
            $table->string('expiry_year', 4);
            $table->string('citizen_id', 20)->nullable();
            $table->boolean('is_default')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'is_default']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists(config('sumit-payment.tables.payment_tokens', 'sumit_payment_tokens'));
    }
};
