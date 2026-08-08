<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('orders', 'pemproses_id')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->unsignedBigInteger('pemproses_id')->nullable()->after('kontak_id');
                $table->foreign('pemproses_id')->references('id')->on('pemproses')->onUpdate('cascade')->onDelete('set null');
            });
        }

        // Pindahkan URG dari detail ke order utama
        $urgId = DB::table('pemproses')
            ->whereRaw('UPPER(TRIM(nama)) = ?', ['URG'])
            ->value('id');

        if ($urgId && Schema::hasColumn('order_details', 'pemproses_id')) {
            $orderIds = DB::table('order_details')
                ->where('pemproses_id', $urgId)
                ->whereNotNull('order_id')
                ->distinct()
                ->pluck('order_id');

            if ($orderIds->isNotEmpty()) {
                DB::table('orders')
                    ->whereIn('id', $orderIds)
                    ->whereNull('pemproses_id')
                    ->update(['pemproses_id' => $urgId]);

                DB::table('order_details')
                    ->whereIn('order_id', $orderIds)
                    ->where('pemproses_id', $urgId)
                    ->update(['pemproses_id' => null]);
            }
        }
    }

    public function down(): void
    {
        if (!Schema::hasColumn('orders', 'pemproses_id')) {
            return;
        }

        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['pemproses_id']);
            $table->dropColumn('pemproses_id');
        });
    }
};
