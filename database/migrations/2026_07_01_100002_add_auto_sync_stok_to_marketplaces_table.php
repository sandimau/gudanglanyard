<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('marketplaces', function (Blueprint $table) {
            if (!Schema::hasColumn('marketplaces', 'auto_sync_stok')) {
                $table->boolean('auto_sync_stok')->default(true)->after('tglUploadStok');
            }
            if (!Schema::hasColumn('marketplaces', 'tglSyncStok')) {
                $table->timestamp('tglSyncStok')->nullable()->after('auto_sync_stok');
            }
        });
    }

    public function down(): void
    {
        Schema::table('marketplaces', function (Blueprint $table) {
            if (Schema::hasColumn('marketplaces', 'auto_sync_stok')) {
                $table->dropColumn('auto_sync_stok');
            }
            if (Schema::hasColumn('marketplaces', 'tglSyncStok')) {
                $table->dropColumn('tglSyncStok');
            }
        });
    }
};
