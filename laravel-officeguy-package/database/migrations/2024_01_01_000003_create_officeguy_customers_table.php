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
        Schema::create('officeguy_customers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('external_id', 255)->nullable()->unique(); // SUMIT customer ID
            $table->string('name', 255);
            $table->string('email', 255)->index();
            $table->string('phone', 50)->nullable();
            $table->string('citizen_id', 50)->nullable();
            $table->string('vat_number', 50)->nullable();
            $table->text('address')->nullable();
            $table->string('city', 100)->nullable();
            $table->string('zip_code', 20)->nullable();
            $table->string('country', 2)->default('IL');
            $table->string('language', 10)->default('he');
            $table->boolean('receive_emails')->default(true);
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Foreign key (optional)
            // $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('officeguy_customers');
    }
};
