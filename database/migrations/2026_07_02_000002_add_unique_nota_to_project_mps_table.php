<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('project_mps', function (Blueprint $table) {
            $table->unique('nota', 'project_mps_nota_unique');
        });
    }

    public function down(): void
    {
        Schema::table('project_mps', function (Blueprint $table) {
            $table->dropUnique('project_mps_nota_unique');
        });
    }
};
