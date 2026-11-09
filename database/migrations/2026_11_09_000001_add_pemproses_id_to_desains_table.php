<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('desains', function (Blueprint $table) {
            $table->foreignId('pemproses_id')->nullable()->after('desain_tab_id')->constrained('pemproses')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('desains', function (Blueprint $table) {
            $table->dropConstrainedForeignId('pemproses_id');
        });
    }
};
