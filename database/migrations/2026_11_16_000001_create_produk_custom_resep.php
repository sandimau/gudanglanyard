<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasColumn('produk_kategori_utamas', 'produksi')) {
            Schema::table('produk_kategori_utamas', fn (Blueprint $table) => $table->boolean('produksi')->default(false));
        }
        if (! Schema::hasColumn('produk_models', 'produksi')) {
            Schema::table('produk_models', fn (Blueprint $table) => $table->boolean('produksi')->default(false));
        }
        Schema::table('produk_kategori_utamas', function (Blueprint $table) {
            $table->boolean('is_custom')->default(false);
        });

        $id = DB::table('produk_kategori_utamas')->where('nama', 'Produk Custom')->value('id');
        if ($id) {
            DB::table('produk_kategori_utamas')->where('id', $id)->update(['is_custom' => 1, 'jual' => 1, 'stok' => 0, 'produksi' => 1]);
        } else {
            DB::table('produk_kategori_utamas')->insert([
                'nama' => 'Produk Custom', 'jual' => 1, 'beli' => 0, 'stok' => 0,
                'produksi' => 1, 'is_custom' => 1, 'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        Schema::create('produk_custom_reseps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('produk_id')->constrained('produks')->cascadeOnDelete();
            $table->string('jenis', 12); // bahan | internal
            $table->foreignId('komponen_model_id')->nullable()->constrained('produk_models');
            $table->decimal('jumlah', 12, 2)->default(1);
            $table->string('keterangan')->nullable();
            $table->unsignedInteger('biaya')->nullable();
            $table->boolean('per_order')->default(false);
            $table->timestamps();
        });

        foreach (['order_details', 'project_mp_details'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->string('material_ids')->nullable();
                $table->timestamp('custom_resep_disalin_at')->nullable();
            });
        }

        Schema::create('order_custom_bahans', function (Blueprint $table) {
            $table->id();
            $table->string('sumber', 12); // order | project
            $table->unsignedBigInteger('detail_id');
            $table->foreignId('produk_id')->constrained('produks');
            $table->unsignedInteger('jumlah');
            $table->unsignedInteger('hpp')->default(0);
            $table->foreignId('ambil_barang_id')->nullable()->constrained('ambil_barangs');
            $table->timestamps();
            $table->unique(['sumber', 'detail_id', 'produk_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_custom_bahans');
        foreach (['order_details', 'project_mp_details'] as $tableName) {
            Schema::table($tableName, fn (Blueprint $table) => $table->dropColumn(['material_ids', 'custom_resep_disalin_at']));
        }
        Schema::dropIfExists('produk_custom_reseps');
        Schema::table('produk_kategori_utamas', fn (Blueprint $table) => $table->dropColumn('is_custom'));
    }
};
