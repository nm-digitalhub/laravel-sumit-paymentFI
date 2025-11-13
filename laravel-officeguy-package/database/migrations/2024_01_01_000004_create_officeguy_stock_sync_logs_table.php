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
        Schema::create('officeguy_stock_sync_logs', function (Blueprint $table) {
            $table->id();
            $table->string('product_id', 100)->index();
            $table->string('external_identifier', 255)->nullable();
            $table->string('product_name', 255)->nullable();
            $table->integer('old_stock')->nullable();
            $table->integer('new_stock');
            $table->string('status', 50); // success, failed
            $table->text('error_message')->nullable();
            $table->timestamp('synced_at');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('officeguy_stock_sync_logs');
    }
};
