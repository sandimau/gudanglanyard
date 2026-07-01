<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('chats', function (Blueprint $table) {
            $table->unsignedBigInteger('project_mp_id')->nullable()->after('order_id');
            $table->foreign('project_mp_id')->references('id')->on('project_mps')->onUpdate('cascade')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('chats', function (Blueprint $table) {
            $table->dropForeign(['project_mp_id']);
            $table->dropColumn('project_mp_id');
        });
    }
};
