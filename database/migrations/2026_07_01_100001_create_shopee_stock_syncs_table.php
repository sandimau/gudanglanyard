<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('shopee_stock_syncs')) {
            return;
        }

        Schema::create('shopee_stock_syncs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('produk_id')->unique();
            $table->timestamp('dirty_at')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->integer('last_synced_stock')->nullable();
            $table->text('last_error')->nullable();
            $table->json('synced_marketplaces')->nullable();
            $table->timestamps();

            $table->index('dirty_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shopee_stock_syncs');
    }
};
