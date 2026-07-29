<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('project_mps', 'order_id')) {
            return;
        }

        Schema::table('project_mps', function (Blueprint $table) {
            $table->unsignedBigInteger('order_id')->nullable()->after('marketplace_id');
            $table->foreign('order_id')->references('id')->on('orders')->onUpdate('cascade')->onDelete('set null');
        });
    }

    public function down(): void
    {
        if (!Schema::hasColumn('project_mps', 'order_id')) {
            return;
        }

        Schema::table('project_mps', function (Blueprint $table) {
            $table->dropForeign(['order_id']);
            $table->dropColumn('order_id');
        });
    }
};
