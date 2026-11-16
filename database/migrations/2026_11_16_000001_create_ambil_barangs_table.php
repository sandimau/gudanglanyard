<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ambil_barangs', function (Blueprint $table) {
            $table->id();
            $table->string('sumber', 20);
            $table->unsignedBigInteger('detail_id');
            $table->unsignedBigInteger('produk_id');
            $table->unsignedBigInteger('cabang_id');
            $table->unsignedInteger('jumlah');
            $table->unsignedBigInteger('produk_stok_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->unique(['sumber', 'detail_id']);
            $table->index(['cabang_id', 'produk_id']);
        });

        Schema::create('cek_stok_kosongs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cabang_id');
            $table->unsignedBigInteger('produk_id');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->index(['cabang_id', 'produk_id']);
        });

        // Hanya tandai dokumen lama yang mutasi penjualannya dapat dicocokkan
        // secara utuh per produk. Jangan menebak alokasi dari mutasi parsial.
        $this->backfill('order_details', 'orders', 'order_id', 'order', 'jual', 'marketplace');
        $this->backfill('project_mp_details', 'project_mps', 'project_id', 'project', 'shp');
    }

    private function backfill(string $details, string $parents, string $parentKey, string $sumber, string $kode, ?string $excludeColumn = null): void
    {
        $query = DB::table("{$details} as d")
            ->join("{$parents} as p", 'p.id', '=', "d.{$parentKey}")
            ->join('produks as product', 'product.id', '=', 'd.produk_id')
            ->join('produk_models as model', 'model.id', '=', 'product.produk_model_id')
            ->where('model.stok', 1)
            ->where('d.jumlah', '>', 0)
            ->select('d.id', 'd.produk_id', 'd.jumlah', "d.{$parentKey} as parent_id", 'p.cabang_id');

        if ($details === 'order_details') {
            $query->whereNull('d.deleted_at')->whereNull('p.deleted_at');
        }
        if ($excludeColumn) {
            $query->whereNull("p.{$excludeColumn}");
        }

        foreach ($query->orderBy('d.id')->get()->groupBy(fn ($d) => $d->parent_id . ':' . $d->produk_id) as $group) {
            $first = $group->first();
            if ($sumber === 'order' && DB::table('project_mp_details as pd')
                ->join('produksis as status', 'status.id', '=', 'pd.produksi_id')
                ->where('pd.project_id', $first->parent_id)
                ->where('pd.produk_id', $first->produk_id)
                ->where('status.grup', '!=', 'awal')
                ->where('status.grup', '!=', 'batal')
                ->exists()) {
                // ID order dan project bisa beririsan; bila kedua dokumen
                // pernah diproses, mutasi jual lama tidak bisa dibedakan.
                continue;
            }

            $cabangId = (int) ($first->cabang_id ?: (DB::table('cabangs')->where('kode', 'PUSAT')->value('id') ?? 0));
            $mutasi = DB::table('produk_stoks')
                ->where('kode', $kode)
                ->where('detail_id', $first->parent_id)
                ->where('produk_id', $first->produk_id)
                ->where('cabang_id', $cabangId)
                ->whereNull('deleted_at')
                ->selectRaw('COALESCE(SUM(kurang), 0) as jumlah')
                ->value('jumlah');

            if ((int) $mutasi < $group->sum('jumlah')) {
                continue;
            }

            foreach ($group as $detail) {
                DB::table('ambil_barangs')->insertOrIgnore([
                    'sumber' => $sumber,
                    'detail_id' => $detail->id,
                    'produk_id' => $detail->produk_id,
                    'cabang_id' => $cabangId,
                    'jumlah' => $detail->jumlah,
                    'created_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('cek_stok_kosongs');
        Schema::dropIfExists('ambil_barangs');
    }
};
