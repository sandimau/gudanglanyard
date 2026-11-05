<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\DirekturKeuanganController;
use Illuminate\Http\Request;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DirekturKeuanganTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');

        Schema::create('hutangs', function (Blueprint $t) {
            $t->id(); $t->date('tanggal'); $t->string('jenis'); $t->integer('jumlah');
            $t->unsignedBigInteger('kontak_id')->nullable(); $t->unsignedBigInteger('detail_id')->nullable();
        });
        Schema::create('hutang_details', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('hutang_id'); $t->date('tanggal'); $t->integer('jumlah');
        });
        Schema::create('kontaks', function (Blueprint $t) {
            $t->id(); $t->string('nama');
        });
        foreach (['orders', 'project_mps'] as $table) {
            Schema::create($table, function (Blueprint $t) use ($table) {
                $t->id(); $t->integer('total'); $t->timestamp('created_at'); $t->softDeletes();
                if ($table === 'orders') { $t->integer('marketplace')->nullable(); $t->integer('bayar')->nullable(); }
            });
        }
        foreach (['order_details', 'project_mp_details'] as $table) {
            Schema::create($table, function (Blueprint $t) use ($table) {
                $t->id(); $t->unsignedBigInteger($table === 'order_details' ? 'order_id' : 'project_id');
                $t->unsignedBigInteger('produk_id'); $t->integer('harga'); $t->integer('jumlah');
                $t->integer('hpp')->nullable(); $t->unsignedBigInteger('produksi_id')->nullable(); $t->softDeletes();
            });
        }
        Schema::create('belanjas', function (Blueprint $t) {
            $t->id(); $t->timestamp('created_at'); $t->softDeletes();
        });
        Schema::create('belanja_details', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('belanja_id'); $t->unsignedBigInteger('produk_id');
            $t->integer('harga'); $t->integer('jumlah');
        });
        Schema::create('produks', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('produk_model_id'); $t->integer('hpp')->nullable(); $t->string('nama')->nullable();
        });
        Schema::create('produk_models', function (Blueprint $t) {
            $t->id(); $t->integer('stok')->nullable(); $t->unsignedBigInteger('kategori_id')->nullable();
        });
        Schema::create('produk_kategoris', function (Blueprint $t) {
            $t->id(); $t->string('nama');
        });
        foreach (['penggajians', 'tunjangans'] as $table) {
            Schema::create($table, function (Blueprint $t) use ($table) {
                $t->id(); $t->timestamp('created_at'); $t->integer($table === 'penggajians' ? 'total' : 'jumlah');
                if ($table === 'penggajians') { $t->integer('kasbon')->nullable(); }
                $t->softDeletes();
            });
        }
        Schema::create('akun_kategoris', function (Blueprint $t) {
            $t->id(); $t->softDeletes();
        });
        Schema::create('akun_details', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('akun_kategori_id'); $t->integer('saldo'); $t->softDeletes();
        });
    }

    public function test_saldo_lama_baru_dan_pembayaran_tidak_menggandakan_hutang(): void
    {
        $awal = today()->subDays(29);
        DB::table('kontaks')->insert(['id' => 1, 'nama' => 'Pemasok A']);
        DB::table('hutangs')->insert([
            ['id' => 1, 'tanggal' => $awal->copy()->subDay()->toDateString(), 'jenis' => 'belanja', 'jumlah' => 1000, 'kontak_id' => 1],
            ['id' => 2, 'tanggal' => $awal->toDateString(), 'jenis' => 'belanja produksi', 'jumlah' => 600, 'kontak_id' => 1],
            ['id' => 3, 'tanggal' => today()->toDateString(), 'jenis' => 'piutang', 'jumlah' => 900, 'kontak_id' => 1],
        ]);
        DB::table('hutang_details')->insert([
            ['hutang_id' => 1, 'tanggal' => $awal->copy()->subDay()->toDateString(), 'jumlah' => 100],
            ['hutang_id' => 1, 'tanggal' => $awal->toDateString(), 'jumlah' => 200],
            ['hutang_id' => 1, 'tanggal' => today()->toDateString(), 'jumlah' => 100],
            ['hutang_id' => 2, 'tanggal' => today()->toDateString(), 'jumlah' => 150],
            ['hutang_id' => 2, 'tanggal' => today()->addDay()->toDateString(), 'jumlah' => 100],
        ]);
        DB::table('orders')->insert(['total' => 2000, 'created_at' => $awal->copy()->startOfDay()->toDateTimeString()]);
        DB::table('project_mps')->insert(['total' => 500, 'created_at' => now()->toDateTimeString()]);

        $data = (new DirekturKeuanganController())->index()->getData();

        $this->assertEquals(900, $data['saldoAwal']);
        $this->assertEquals(600, $data['hutangBaru']);
        $this->assertEquals(450, $data['dibayar']);
        $this->assertEquals(1050, $data['saldoAkhir']);
        $this->assertEquals(600, $data['tunggakanLama']);
        $this->assertCount(2, $data['prioritas']);
    }

    public function test_keuangan_memisahkan_sumber_omzet_hpp_operasional_dan_tagihan(): void
    {
        $awal = today()->subDays(29)->startOfDay()->toDateTimeString();
        DB::table('produk_kategoris')->insert(['id' => 1, 'nama' => 'Kemasan']);
        DB::table('produk_models')->insert(['id' => 1, 'kategori_id' => 1, 'stok' => null]);
        DB::table('produks')->insert(['id' => 1, 'produk_model_id' => 1, 'hpp' => 80]);
        DB::table('orders')->insert([
            ['id' => 1, 'created_at' => $awal, 'total' => 2000, 'marketplace' => null, 'bayar' => 500, 'deleted_at' => null],
            ['id' => 2, 'created_at' => $awal, 'total' => 300, 'marketplace' => 1, 'bayar' => 300, 'deleted_at' => null],
            ['id' => 3, 'created_at' => $awal, 'total' => 999, 'marketplace' => null, 'bayar' => 0, 'deleted_at' => now()->toDateTimeString()],
        ]);
        DB::table('project_mps')->insert(['id' => 1, 'created_at' => $awal, 'total' => 500]);
        DB::table('order_details')->insert(['order_id' => 1, 'produk_id' => 1, 'harga' => 100, 'jumlah' => 2, 'hpp' => 50]);
        DB::table('project_mp_details')->insert(['project_id' => 1, 'produk_id' => 1, 'harga' => 100, 'jumlah' => 1, 'hpp' => 0]);
        DB::table('belanjas')->insert(['id' => 1, 'created_at' => $awal]);
        DB::table('belanja_details')->insert(['belanja_id' => 1, 'produk_id' => 1, 'harga' => 20, 'jumlah' => 3]);
        DB::table('penggajians')->insert(['created_at' => $awal, 'total' => 200, 'kasbon' => 30]);
        DB::table('tunjangans')->insert(['created_at' => $awal, 'jumlah' => 40]);
        DB::table('hutangs')->insert(['id' => 1, 'tanggal' => today()->toDateString(), 'jenis' => 'piutang', 'jumlah' => 800]);
        DB::table('hutang_details')->insert(['hutang_id' => 1, 'tanggal' => today()->toDateString(), 'jumlah' => 200]);

        $data = (new DirekturKeuanganController())->keuangan()->getData();

        $this->assertEquals(2000, $data['offline']);
        $this->assertEquals(300, $data['onlineOrder']);
        $this->assertEquals(500, $data['onlineProject']);
        $this->assertEquals(2800, $data['omzet']);
        $this->assertEquals(180, $data['hpp']);
        $this->assertEquals(60, $data['operasional']);
        $this->assertEquals(230, $data['gaji']);
        $this->assertEquals(40, $data['tunjangan']);
        $this->assertEquals(600, $data['piutang']);
        $this->assertEquals(1500, $data['belumDibayarOrder']);

        $controller = new DirekturKeuanganController();
        $margin = $controller->detail(Request::create('/admin/ringkasan-detail/keuangan/margin'), 'keuangan', 'margin')->getData();
        $kategori = $controller->detail(Request::create('/admin/ringkasan-detail/keuangan/kategori?kategori=Kemasan'), 'keuangan', 'kategori')->getData();
        $this->assertEquals(2620, $margin['rows']->sum('nilai'));
        $this->assertCount(1, $kategori['rows']);
        $this->assertEquals(60, $kategori['rows']->first()->nilai);
    }

    public function test_popup_hutang_menampilkan_pembayaran_periode_dan_riwayat(): void
    {
        DB::table('hutangs')->insert(['id' => 1, 'tanggal' => today()->subDays(40)->toDateString(), 'jenis' => 'belanja', 'jumlah' => 1000]);
        DB::table('hutang_details')->insert([
            ['hutang_id' => 1, 'tanggal' => today()->subDays(35)->toDateString(), 'jumlah' => 100],
            ['hutang_id' => 1, 'tanggal' => today()->toDateString(), 'jumlah' => 200],
        ]);

        $controller = new DirekturKeuanganController();
        $payments = $controller->detail(Request::create('/admin/ringkasan-detail/hutang/bayar'), 'hutang', 'bayar')->getData();
        $history = $controller->detail(Request::create('/admin/ringkasan-detail/hutang/transaksi?id=1'), 'hutang', 'transaksi')->getData();

        $this->assertCount(1, $payments['rows']);
        $this->assertEquals(200, $payments['rows']->first()->nilai);
        $this->assertEquals(700, $history['rows']->first()->nilai);
        $this->assertCount(2, $history['history']);
    }

    public function test_popup_keuangan_mengikuti_filter_omzet(): void
    {
        DB::table('orders')->insert([
            ['id' => 1, 'created_at' => today()->toDateTimeString(), 'total' => 700, 'marketplace' => null],
            ['id' => 2, 'created_at' => today()->toDateTimeString(), 'total' => 300, 'marketplace' => 1],
        ]);

        $controller = new DirekturKeuanganController();
        $offline = $controller->detail(Request::create('/admin/ringkasan-detail/keuangan/offline'), 'keuangan', 'offline')->getData();

        $this->assertCount(1, $offline['rows']);
        $this->assertEquals(700, $offline['rows']->first()->nilai);
    }
}
