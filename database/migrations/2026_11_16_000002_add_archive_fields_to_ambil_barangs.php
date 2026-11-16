<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ambil_barangs', function (Blueprint $table) {
            $table->string('nota', 100)->nullable();
            $table->string('pembeli')->nullable();
            $table->string('jenis', 30)->nullable();
            $table->string('shipping')->nullable();
            $table->timestamp('tanggal_order')->nullable();
        });

        // Isi arsip untuk pengambilan lama yang detail ordernya masih tersedia.
        DB::table('ambil_barangs')->orderBy('id')->chunkById(200, function ($rows) {
            foreach ($rows as $row) {
                if ($row->sumber === 'order') {
                    $parent = DB::table('order_details as d')
                        ->join('orders as o', 'o.id', '=', 'd.order_id')
                        ->leftJoin('kontaks as k', 'k.id', '=', 'o.kontak_id')
                        ->where('d.id', $row->detail_id)
                        ->select('o.nota', 'o.pengiriman as shipping', 'o.created_at as tanggal_order', 'k.nama as pembeli')
                        ->first();
                    $jenis = 'Offline';
                } else {
                    $parent = DB::table('project_mp_details as d')
                        ->join('project_mps as p', 'p.id', '=', 'd.project_id')
                        ->where('d.id', $row->detail_id)
                        ->select('p.nota', 'p.shipping', 'p.created_at as tanggal_order', 'p.konsumen as pembeli')
                        ->first();
                    $jenis = 'Produk';
                }

                if ($parent) {
                    DB::table('ambil_barangs')->where('id', $row->id)->update([
                        'nota' => $parent->nota,
                        'pembeli' => $parent->pembeli,
                        'jenis' => $jenis,
                        'shipping' => $parent->shipping,
                        'tanggal_order' => $parent->tanggal_order,
                    ]);
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('ambil_barangs', function (Blueprint $table) {
            $table->dropColumn(['nota', 'pembeli', 'jenis', 'shipping', 'tanggal_order']);
        });
    }
};
