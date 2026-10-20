<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\PenggajianController;
use App\Models\Member;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class FreelancePenggajianKasbonTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');
        config()->set('app.url', 'http://localhost');
        URL::forceRootUrl('http://localhost');
        DB::purge('sqlite');
        $this->withoutMiddleware();

        Schema::create('members', function (Blueprint $table) {
            $table->id();
            $table->string('nama_lengkap');
            $table->integer('upah');
            $table->integer('lembur');
            $table->softDeletes();
        });
        Schema::create('akun_details', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->integer('saldo')->nullable();
            $table->softDeletes();
        });
        Schema::create('akun_last_saldos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('akun_detail_id');
            $table->integer('tahun');
            $table->integer('saldo');
            $table->timestamps();
        });
        Schema::create('kasbons', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('member_id');
            $table->integer('saldo');
            $table->integer('pengeluaran')->nullable();
            $table->string('keterangan')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('freelance_tagihans', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('member_id');
            $table->date('tanggal');
            $table->integer('nominal_upah');
            $table->string('dibayar');
            $table->unsignedBigInteger('penggajian_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('lemburs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('member_id');
            $table->string('dibayar');
            $table->string('status');
            $table->integer('jam');
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('penggajians', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('member_id');
            $table->unsignedBigInteger('akun_detail_id');
            $table->integer('jam_lembur');
            $table->integer('lembur');
            $table->integer('pokok');
            $table->integer('kasbon');
            $table->integer('jumlah_lain');
            $table->string('lain_lain')->nullable();
            $table->integer('total');
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('buku_besars', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('akun_detail_id');
            $table->string('ket');
            $table->string('kode');
            $table->integer('kredit');
            $table->integer('debet');
            $table->timestamps();
        });
    }

    public function test_kasbon_deducts_from_freelance_pay_and_balance(): void
    {
        [$memberId, $akunId] = $this->seedFreelance(150000, 200000);
        DB::table('lemburs')->insert([
            'member_id' => $memberId,
            'dibayar' => 'belum',
            'status' => 'approved',
            'jam' => 1,
        ]);

        $form = app(PenggajianController::class)->createFreelance(Member::findOrFail($memberId));
        $this->assertSame(150000, $form->getData()['totalKasbon']);
        $compiledForm = app('blade.compiler')->compileString(file_get_contents(
            resource_path('views/admin/penggajians/createFreelance.blade.php')
        ));
        $this->assertStringContainsString('name="kasbon"', $compiledForm);

        $this->postJson('/admin/freelance/penggajian/create', [
            'member_id' => $memberId,
            'akun_detail_id' => $akunId,
            'jumlah_hari' => 1,
            'jumlah_lain' => 5000,
            'kasbon' => 100000,
            'total' => 999999,
        ])->assertOk();

        $this->assertDatabaseHas('penggajians', [
            'member_id' => $memberId,
            'pokok' => 1,
            'kasbon' => 100000,
            'total' => 125000,
        ]);
        $this->assertDatabaseHas('kasbons', [
            'member_id' => $memberId,
            'pengeluaran' => 100000,
            'saldo' => 50000,
        ]);
        $this->assertDatabaseHas('buku_besars', ['kredit' => 125000]);
        $this->assertDatabaseHas('freelance_tagihans', ['member_id' => $memberId, 'dibayar' => 'sudah']);
    }

    public function test_kasbon_cannot_exceed_balance_or_pay(): void
    {
        [$memberId, $akunId] = $this->seedFreelance(150000, 100000);
        $payload = [
            'member_id' => $memberId,
            'akun_detail_id' => $akunId,
            'jumlah_hari' => 1,
            'kasbon' => 160000,
        ];

        $this->postJson('/admin/freelance/penggajian/create', $payload)
            ->assertUnprocessable()->assertJsonValidationErrors('kasbon');
        $this->postJson('/admin/freelance/penggajian/create', array_merge($payload, ['kasbon' => 120000]))
            ->assertUnprocessable()->assertJsonValidationErrors('kasbon');

        $this->assertDatabaseCount('penggajians', 0);
        $this->assertDatabaseCount('kasbons', 1);
        $this->assertDatabaseCount('buku_besars', 0);
    }

    public function test_kasbon_also_deducts_from_manual_day_pay(): void
    {
        [$memberId, $akunId] = $this->seedFreelance(150000, 100000);
        DB::table('freelance_tagihans')->delete();

        $this->postJson('/admin/freelance/penggajian/create', [
            'member_id' => $memberId,
            'akun_detail_id' => $akunId,
            'jumlah_hari' => 2,
            'kasbon' => 50000,
        ])->assertOk();

        $this->assertDatabaseHas('penggajians', [
            'member_id' => $memberId,
            'pokok' => 2,
            'kasbon' => 50000,
            'total' => 150000,
        ]);
        $this->assertDatabaseHas('kasbons', [
            'member_id' => $memberId,
            'pengeluaran' => 50000,
            'saldo' => 100000,
        ]);
    }

    private function seedFreelance(int $saldoKasbon, int $nominalUpah): array
    {
        $memberId = DB::table('members')->insertGetId([
            'nama_lengkap' => 'Freelance Uji',
            'upah' => 100000,
            'lembur' => 20000,
        ]);
        $akunId = DB::table('akun_details')->insertGetId(['nama' => 'Kas Uji']);
        DB::table('kasbons')->insert(['member_id' => $memberId, 'saldo' => $saldoKasbon]);
        DB::table('freelance_tagihans')->insert([
            'member_id' => $memberId,
            'tanggal' => '2026-10-20',
            'nominal_upah' => $nominalUpah,
            'dibayar' => 'belum',
        ]);

        return [$memberId, $akunId];
    }
}
