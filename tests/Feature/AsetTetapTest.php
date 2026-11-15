<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\AsetController;
use App\Models\AkunDetail;
use App\Models\Hutang;
use App\Models\Produk;
use App\Models\ProdukAset;
use App\Models\ProdukAsetDetail;
use App\Services\Accounting\AsetPenyusutanService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class AsetTetapTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');

        $this->buatTabelDasar();
        $this->jalankanMigrasiAset();

        // Gate "before" dari Spatie hanya berjalan untuk user login, jadi untuk
        // pengujian controller kita definisikan izin secara eksplisit.
        Gate::define('aset_access', fn ($user = null) => true);
        Gate::define('aset_manage', fn ($user = null) => true);
    }

    public function test_pembelian_tunai_membuat_saldo_awal_dan_jurnal(): void
    {
        [$produk, $kontak] = $this->buatProdukDanKontak();
        $kas = $this->buatKas();

        app(AsetController::class)->store(Request::create('/admin/aset-tetap', 'POST', [
            'produk_id' => $produk->id,
            'kontak_id' => $kontak->id,
            'harga' => 1500000,
            'jumlah' => 2,
            'waktuPenyusutan' => 5,
            'pembayaran' => 'tunai',
            'akun_detail_id' => $kas->id,
        ]));

        $aset = ProdukAset::firstOrFail();
        $jurnal = DB::table('akun_jurnals')->where('source_type', 'aset_pembelian')->first();

        $this->assertSame(3000000, (int) $aset->saldo);
        $this->assertDatabaseHas('produk_aset_details', [
            'aset_id' => $aset->id,
            'kode' => 'sld',
            'debet' => 3000000,
            'kredit' => 0,
        ]);
        $this->assertNotNull($jurnal);
        $this->assertSame('aset-beli-' . $aset->id, $jurnal->idempoten_kunci);
        $this->assertSame(0, Hutang::count());

        $akunAset = AkunDetail::where('nama', '12101 Aset Tetap')->firstOrFail();

        $this->assertDatabaseHas('buku_besars', [
            'jurnal_id' => $jurnal->id,
            'akun_detail_id' => $akunAset->id,
            'debet' => 3000000,
            'kredit' => 0,
        ]);
        $this->assertDatabaseHas('buku_besars', [
            'jurnal_id' => $jurnal->id,
            'akun_detail_id' => $kas->id,
            'debet' => 0,
            'kredit' => 3000000,
        ]);

        $this->assertJurnalSeimbang($jurnal->id);
    }

    public function test_pembelian_hutang_membuat_hutang_usaha_dan_jurnal_hutang(): void
    {
        [$produk, $kontak] = $this->buatProdukDanKontak();

        app(AsetController::class)->store(Request::create('/admin/aset-tetap', 'POST', [
            'produk_id' => $produk->id,
            'kontak_id' => $kontak->id,
            'harga' => 900000,
            'jumlah' => 1,
            'waktuPenyusutan' => 3,
            'pembayaran' => 'hutang',
        ]));

        $aset = ProdukAset::firstOrFail();
        $hutang = Hutang::first();
        $this->assertNotNull($hutang);
        $jurnal = DB::table('akun_jurnals')->where('source_type', 'aset_pembelian')->first();
        $this->assertNotNull($jurnal);
        $akunHutang = AkunDetail::where('nama', '21101 Hutang Usaha')->firstOrFail();

        $this->assertSame('aset', $hutang->jenis);
        $this->assertSame(900000, (int) $hutang->jumlah);
        $this->assertSame($aset->id, (int) $hutang->detail_id);
        $this->assertNull($aset->akun_detail_id);

        $this->assertDatabaseHas('buku_besars', [
            'jurnal_id' => $jurnal->id,
            'akun_detail_id' => $akunHutang->id,
            'debet' => 0,
            'kredit' => 900000,
        ]);

        $this->assertJurnalSeimbang($jurnal->id);
    }

    public function test_penyusutan_bulanan_hanya_sekali_dan_menyesuaikan_saldo(): void
    {
        $aset = $this->buatAset(1200000, 1);

        $hasil = app(AsetPenyusutanService::class)->jalankan();

        $this->assertSame(1, $hasil['diproses']);
        $this->assertSame(1100000, (int) $aset->fresh()->saldo);
        $this->assertDatabaseHas('produk_aset_details', [
            'aset_id' => $aset->id,
            'kode' => 'sst',
            'debet' => 0,
            'kredit' => 100000,
        ]);

        $sst = ProdukAsetDetail::where('kode', 'sst')->firstOrFail();
        $this->assertNotNull($sst->jurnal_id);
        $this->assertJurnalSeimbang($sst->jurnal_id);

        $akunBeban = AkunDetail::where('nama', '52301 Beban Penyusutan Aset Tetap')->firstOrFail();
        $akunAkumulasi = AkunDetail::where('nama', '12201 Akumulasi Penyusutan Aset Tetap')->firstOrFail();

        $this->assertDatabaseHas('buku_besars', [
            'jurnal_id' => $sst->jurnal_id,
            'akun_detail_id' => $akunBeban->id,
            'debet' => 100000,
            'kredit' => 0,
        ]);
        $this->assertDatabaseHas('buku_besars', [
            'jurnal_id' => $sst->jurnal_id,
            'akun_detail_id' => $akunAkumulasi->id,
            'debet' => 0,
            'kredit' => 100000,
        ]);

        $hasilKedua = app(AsetPenyusutanService::class)->jalankan();

        $this->assertSame(0, $hasilKedua['diproses']);
        $this->assertSame(1, $hasilKedua['dilewati']);
        $this->assertSame(1, ProdukAsetDetail::where('kode', 'sst')->count());
        $this->assertSame(1100000, (int) $aset->fresh()->saldo);
    }

    public function test_penyusutan_menghabiskan_saldo_dan_sisa_di_bawah_seratus(): void
    {
        $aset = $this->buatAset(1200000, 1);

        // Saldo tersisa Rp100.050, penyusutan bulanan Rp100.000, sisa Rp50 ikut dihabiskan.
        ProdukAsetDetail::create([
            'aset_id' => $aset->id,
            'tanggal' => now()->subMonth()->toDateString(),
            'kode' => 'sst',
            'keterangan' => 'Penyusutan bulan lalu',
            'debet' => 0,
            'kredit' => 1099950,
        ]);

        $this->assertSame(100050, $aset->fresh()->saldoDetail());

        app(AsetPenyusutanService::class)->jalankan();

        $this->assertSame(0, (int) $aset->fresh()->saldo);
        $this->assertDatabaseHas('produk_aset_details', [
            'aset_id' => $aset->id,
            'kode' => 'sst',
            'debet' => 0,
            'kredit' => 100050,
        ]);
    }

    public function test_penyesuaian_manual_tidak_membuat_jurnal(): void
    {
        $aset = $this->buatAset(1000000, 5);
        $jurnalAwal = DB::table('akun_jurnals')->count();

        app(AsetController::class)->update(new Request([
            'jenis_penyesuaian' => 'tambah',
            'nominal' => 250000,
            'keterangan' => 'Rekondisi',
        ]), $aset);

        $this->assertDatabaseHas('produk_aset_details', [
            'aset_id' => $aset->id,
            'kode' => 'pls',
            'debet' => 250000,
            'kredit' => 0,
        ]);
        $this->assertSame(1250000, (int) $aset->fresh()->saldo);

        app(AsetController::class)->update(new Request([
            'jenis_penyesuaian' => 'kurang',
            'nominal' => 100000,
        ]), $aset);

        $this->assertDatabaseHas('produk_aset_details', [
            'aset_id' => $aset->id,
            'kode' => 'sst',
            'debet' => 0,
            'kredit' => 100000,
        ]);
        $this->assertSame(1150000, (int) $aset->fresh()->saldo);
        $this->assertSame($jurnalAwal, DB::table('akun_jurnals')->count());

        $this->expectException(ValidationException::class);
        app(AsetController::class)->update(new Request([
            'jenis_penyesuaian' => 'kurang',
            'nominal' => 9999999,
        ]), $aset);
    }

    public function test_penjualan_aset_untung_dan_keluar_dari_daftar_aktif(): void
    {
        [$produk, $kontak] = $this->buatProdukDanKontak();
        $kas = $this->buatKas();
        $aset = $this->buatAset(1200000, 1, $produk, $kontak);

        // Susut bulan lalu Rp100.000 dan penambahan nilai Rp50.000.
        ProdukAsetDetail::create([
            'aset_id' => $aset->id,
            'tanggal' => now()->subMonth()->toDateString(),
            'kode' => 'sst',
            'debet' => 0,
            'kredit' => 100000,
        ]);
        ProdukAsetDetail::create([
            'aset_id' => $aset->id,
            'tanggal' => now()->subMonth()->toDateString(),
            'kode' => 'pls',
            'debet' => 50000,
            'kredit' => 0,
        ]);

        $this->assertSame(1150000, $aset->fresh()->saldoDetail());

        app(AsetController::class)->jualStore(Request::create('/admin/aset-tetap/' . $aset->id . '/jual', 'POST', [
            'harga_jual' => 1300000,
            'akun_detail_id' => $kas->id,
        ]), $aset);

        $aset->refresh();
        $this->assertSame(0, (int) $aset->saldo);
        $this->assertSame(0, ProdukAset::aktif()->count());
        $this->assertSame(1, ProdukAset::habis()->count());

        $jual = ProdukAsetDetail::where('kode', 'jual')->firstOrFail();
        $this->assertSame(1150000, (int) $jual->kredit);
        $this->assertNotNull($jual->jurnal_id);
        $this->assertJurnalSeimbang($jual->jurnal_id);

        $akunAset = AkunDetail::where('nama', '12101 Aset Tetap')->firstOrFail();
        $akunAkumulasi = AkunDetail::where('nama', '12201 Akumulasi Penyusutan Aset Tetap')->firstOrFail();
        $akunUntung = AkunDetail::where('nama', '42101 Keuntungan Penjualan Aset')->firstOrFail();

        $this->assertDatabaseHas('buku_besars', [
            'jurnal_id' => $jual->jurnal_id,
            'akun_detail_id' => $kas->id,
            'debet' => 1300000,
            'kredit' => 0,
        ]);
        $this->assertDatabaseHas('buku_besars', [
            'jurnal_id' => $jual->jurnal_id,
            'akun_detail_id' => $akunAkumulasi->id,
            'debet' => 100000,
            'kredit' => 0,
        ]);
        $this->assertDatabaseHas('buku_besars', [
            'jurnal_id' => $jual->jurnal_id,
            'akun_detail_id' => $akunAset->id,
            'debet' => 0,
            'kredit' => 1250000,
        ]);
        $this->assertDatabaseHas('buku_besars', [
            'jurnal_id' => $jual->jurnal_id,
            'akun_detail_id' => $akunUntung->id,
            'debet' => 0,
            'kredit' => 150000,
        ]);
    }

    public function test_penjualan_aset_rugi(): void
    {
        [$produk, $kontak] = $this->buatProdukDanKontak();
        $kas = $this->buatKas();
        $aset = $this->buatAset(1200000, 1, $produk, $kontak);

        ProdukAsetDetail::create([
            'aset_id' => $aset->id,
            'tanggal' => now()->subMonth()->toDateString(),
            'kode' => 'sst',
            'debet' => 0,
            'kredit' => 100000,
        ]);

        app(AsetController::class)->jualStore(Request::create('/admin/aset-tetap/' . $aset->id . '/jual', 'POST', [
            'harga_jual' => 900000,
            'akun_detail_id' => $kas->id,
        ]), $aset);

        $jual = ProdukAsetDetail::where('kode', 'jual')->firstOrFail();
        $akunRugi = AkunDetail::where('nama', '55101 Kerugian Penjualan Aset')->firstOrFail();

        $this->assertJurnalSeimbang($jual->jurnal_id);
        $this->assertDatabaseHas('buku_besars', [
            'jurnal_id' => $jual->jurnal_id,
            'akun_detail_id' => $akunRugi->id,
            'debet' => 200000,
            'kredit' => 0,
        ]);
        $this->assertSame(0, (int) $aset->fresh()->saldo);
    }

    public function test_riwayat_menghitung_saldo_berjalan_urut_id(): void
    {
        $aset = $this->buatAset(1000000, 5);

        ProdukAsetDetail::create([
            'aset_id' => $aset->id, 'tanggal' => now()->toDateString(), 'kode' => 'sst',
            'debet' => 0, 'kredit' => 100000,
        ]);
        ProdukAsetDetail::create([
            'aset_id' => $aset->id, 'tanggal' => now()->toDateString(), 'kode' => 'pls',
            'debet' => 200000, 'kredit' => 0,
        ]);
        ProdukAsetDetail::create([
            'aset_id' => $aset->id, 'tanggal' => now()->toDateString(), 'kode' => 'sst',
            'debet' => 0, 'kredit' => 50000,
        ]);

        $view = app(AsetController::class)->riwayat($aset->fresh());
        $saldo = $view->getData()['details']->pluck('saldo_berjalan')->all();

        $this->assertSame([1000000, 900000, 1100000, 1050000], array_map('intval', $saldo));
    }

    public function test_endpoint_cron_menjalankan_penyusutan_bulan_berjalan(): void
    {
        $aset = $this->buatAset(1200000, 1);

        $this->getJson('/cron/aset-penyusutan')
            ->assertOk()
            ->assertJson([
                'ok' => true,
                'periode' => now()->format('Y-m'),
                'diproses' => 1,
                'dilewati' => 0,
                'gagal' => 0,
            ]);

        $this->assertSame(1100000, (int) $aset->fresh()->saldo);
    }

    public function test_endpoint_cron_menolak_token_yang_salah(): void
    {
        config(['app.cron_token' => 'rahasia-cron']);

        $this->getJson('/cron/aset-penyusutan')->assertForbidden();
        $this->getJson('/cron/aset-penyusutan?token=salah')->assertForbidden();

        $aset = $this->buatAset(1200000, 1);

        $this->getJson('/cron/aset-penyusutan?token=rahasia-cron')
            ->assertOk()
            ->assertJson(['diproses' => 1]);

        $this->assertSame(1100000, (int) $aset->fresh()->saldo);
    }

    public function test_migrasi_aset_idempoten(): void
    {
        $this->jalankanMigrasiAset();

        $this->assertSame(1, DB::table('akun_details')->where('nama', '12101 Aset Tetap')->count());
        $this->assertSame(1, DB::table('permissions')->where('name', 'aset_access')->count());
        $this->assertSame(1, DB::table('permissions')->where('name', 'aset_manage')->count());
    }

    private function jalankanMigrasiAset(): void
    {
        foreach ([
            '2026_11_15_000001_create_akun_jurnals_tables.php',
            '2026_11_15_000002_create_produk_asets_tables.php',
            '2026_11_15_000003_seed_akun_aset_dan_permissions.php',
        ] as $file) {
            (require base_path('database/migrations/' . $file))->up();
        }
    }

    private function buatTabelDasar(): void
    {
        Schema::create('cabangs', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->string('kode')->nullable();
            $table->integer('status')->default(1);
            $table->timestamps();
        });
        DB::table('cabangs')->insert([
            'id' => 1, 'nama' => 'Pusat', 'kode' => 'PUSAT', 'status' => 1,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        Schema::create('akun_kategoris', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->unsignedBigInteger('akun_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
        foreach ([1 => 'kas', 3 => 'aset tetap', 4 => 'hutang', 6 => 'pendapatan', 7 => 'operasional', 8 => 'marketplace'] as $id => $nama) {
            DB::table('akun_kategoris')->insert([
                'id' => $id, 'nama' => $nama, 'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        Schema::create('akun_details', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->unsignedBigInteger('akun_kategori_id');
            $table->bigInteger('saldo')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('buku_besars', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('akun_detail_id');
            $table->string('kode');
            $table->string('ket')->nullable();
            $table->bigInteger('debet')->default(0);
            $table->bigInteger('kredit')->default(0);
            $table->unsignedBigInteger('detail_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->timestamps();
        });

        Schema::create('akun_last_saldos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('akun_detail_id');
            $table->integer('tahun');
            $table->bigInteger('saldo')->default(0);
            $table->timestamps();
        });

        Schema::create('sistems', function (Blueprint $table) {
            $table->id();
            $table->string('nama')->nullable();
            $table->text('isi')->nullable();
            $table->timestamps();
        });

        Schema::create('produk_kategoris', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cabang_id')->nullable();
            $table->string('nama');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('produk_models', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cabang_id')->nullable();
            $table->string('nama');
            $table->bigInteger('harga')->default(0);
            $table->string('satuan')->nullable();
            $table->unsignedBigInteger('kategori_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('produks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cabang_id')->nullable();
            $table->string('nama');
            $table->integer('status')->default(1);
            $table->unsignedBigInteger('produk_model_id')->nullable();
            $table->timestamps();
        });

        Schema::create('kontaks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cabang_id')->nullable();
            $table->string('nama');
            $table->string('perusahaan')->nullable();
            $table->boolean('supplier')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('hutangs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('kontak_id');
            $table->date('tanggal');
            $table->bigInteger('jumlah');
            $table->string('keterangan')->nullable();
            $table->string('jenis')->nullable();
            $table->unsignedBigInteger('akun_detail_id')->nullable();
            $table->unsignedBigInteger('detail_id')->nullable();
            $table->timestamps();
        });

        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('guard_name');
            $table->timestamps();
        });
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('guard_name');
            $table->timestamps();
        });
        Schema::create('role_has_permissions', function (Blueprint $table) {
            $table->unsignedBigInteger('permission_id');
            $table->unsignedBigInteger('role_id');
        });
        Schema::create('model_has_permissions', function (Blueprint $table) {
            $table->unsignedBigInteger('permission_id');
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
        });
        Schema::create('model_has_roles', function (Blueprint $table) {
            $table->unsignedBigInteger('role_id');
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
        });
    }

    private function buatProdukDanKontak(): array
    {
        $kategoriId = DB::table('produk_kategoris')->insertGetId([
            'nama' => 'Elektronik', 'created_at' => now(), 'updated_at' => now(),
        ]);
        $modelId = DB::table('produk_models')->insertGetId([
            'nama' => 'Laptop', 'harga' => 1500000, 'satuan' => 'unit',
            'kategori_id' => $kategoriId, 'created_at' => now(), 'updated_at' => now(),
        ]);
        $produkId = DB::table('produks')->insertGetId([
            'nama' => 'Laptop Pro', 'status' => 1, 'produk_model_id' => $modelId,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $kontakId = DB::table('kontaks')->insertGetId([
            'nama' => 'Supplier Jaya', 'supplier' => 1, 'created_at' => now(), 'updated_at' => now(),
        ]);

        return [Produk::findOrFail($produkId), \App\Models\Kontak::findOrFail($kontakId)];
    }

    private function buatKas(): AkunDetail
    {
        return AkunDetail::create([
            'nama' => 'Kas Utama',
            'akun_kategori_id' => 1,
            'saldo' => 0,
        ]);
    }

    private function buatAset(int $nilai, int $tahun, ?Produk $produk = null, $kontak = null): ProdukAset
    {
        if (! $produk) {
            [$produk, $kontak] = $this->buatProdukDanKontak();
        }

        $aset = ProdukAset::create([
            'produk_id' => $produk->id,
            'kontak_id' => $kontak?->id,
            'tanggal_beli' => now()->subYear()->toDateString(),
            'waktuPenyusutan' => $tahun,
            'harga' => $nilai,
            'jumlah' => 1,
            'saldo' => $nilai,
        ]);

        ProdukAsetDetail::create([
            'aset_id' => $aset->id,
            'tanggal' => $aset->tanggal_beli->toDateString(),
            'kode' => 'sld',
            'keterangan' => 'Saldo awal',
            'debet' => $nilai,
            'kredit' => 0,
        ]);

        return $aset->fresh();
    }

    private function assertJurnalSeimbang(int $jurnalId): void
    {
        $jurnal = DB::table('akun_jurnals')->where('id', $jurnalId)->first();
        $this->assertNotNull($jurnal, 'Jurnal tidak ditemukan.');

        $debet = (int) DB::table('buku_besars')->where('jurnal_id', $jurnalId)->sum('debet');
        $kredit = (int) DB::table('buku_besars')->where('jurnal_id', $jurnalId)->sum('kredit');

        $this->assertSame($debet, $kredit, 'Jurnal tidak seimbang.');
        $this->assertGreaterThan(0, $debet);
        $this->assertSame($debet, (int) $jurnal->total);
    }
}
