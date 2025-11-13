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
        Schema::create('officeguy_payment_tokens', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();
            $table->string('token', 255)->unique();
            $table->string('card_type', 50)->default('card');
            $table->string('last_four', 4);
            $table->string('expiry_month', 2);
            $table->string('expiry_year', 4);
            $table->string('card_pattern', 50)->nullable();
            $table->string('citizen_id', 50)->nullable();
            $table->string('brand', 50)->nullable();
            $table->boolean('is_default')->default(false);
            $table->timestamps();
            $table->softDeletes();

            // Foreign key (optional, depends on your users table)
            // $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('officeguy_payment_tokens');
    }
};
