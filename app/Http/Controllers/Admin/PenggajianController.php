<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\RespondsToMemberModal;
use App\Models\Absensi;
use App\Models\Gaji;
use App\Models\Level;
use App\Models\Bagian;
use App\Models\Kasbon;
use App\Models\Lembur;
use App\Models\Member;
use App\Models\BukuBesar;
use App\Models\AkunDetail;
use App\Models\Penggajian;
use App\Models\FreelanceTagihan;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use App\Http\Controllers\Controller;

class PenggajianController extends Controller
{
    use RespondsToMemberModal;

    public function create(Member $member)
    {
        if ($member->jenis === 'freelance') {
            return $this->createFreelance($member);
        }

        //get data gaji
        $gaji = Gaji::where('member_id', $member->id)
            ->orderBy('id', 'desc')
            ->first();
        //get data level
        $level = Level::find($gaji->level_id);
        //get data bagian
        $bagian = Bagian::find($gaji->bagian_id);

        //menghitung tahun kerja
        $tgl_masuk = $member->tgl_masuk;
        $diff = abs(strtotime('now') - strtotime($tgl_masuk));
        $tahun_kerja = floor($diff / (365 * 60 * 60 * 24));

        //menghitung tunjangan bagian, performance & lama kerja
        $grade = $bagian->grade;
        $gapok = $level->gaji_pokok;
        $transportasi = $gaji->transportasi;

        if ($transportasi == 1) {
            $transportasi = $level->transportasi;
        } else {
            $transportasi = 0;
        }

        $tBagian = 0.1 * $grade * $gapok;
        $performance = $gaji->performance * 0.1 * $gapok;
        $lamaKerja = ($tahun_kerja * $level->lama_kerja * $gapok) / 100;

        $jmlLembur = Lembur::where([['member_id', '=', $member->id], ['dibayar', '=', 'belum']])->where('status', 'approved')->sum('jam');

        $kasbon = Kasbon::where('member_id', '=', $member->id)
            ->orderBy('id', 'DESC')
            ->first();
        if ($kasbon != null) {
            $totalKasbon = $kasbon->saldo;
        } else {
            $totalKasbon = 0;
        }

        if ($totalKasbon > 500000) {
            $totalKasbon = 500000;
        }

        // $totalLembur = ($gapok / 25 / 8) * 1.5 * $jmlLembur;
        $hargaLembur = $level->harga_lembur;
        if (empty($hargaLembur)) {
            $hargaLembur = ($gapok / 25 / 8) * 1.5;
        }
        $totalLembur = (int) round($hargaLembur * $jmlLembur);

        // Tunjangan kehadiran dihitung per periode gajian member (tgl gajian tiap member beda)
        // Cuti = aman. Sakit/ijin/terlambat/alpha = mengurangi
        // 1x tidak masuk = 50%, 2x+ = hangus. Hanya untuk jenis karyawan.
        [$periodeMulai, $periodeSelesai] = $member->periodeGajian();
        // levels.kehadiran yang masih 0/null dianggap belum diisi, pakai nominal default
        $tunjanganKehadiranPenuh = (int) ($level->kehadiran ?: 100000);
        $jumlahAbsenTidakCuti = 0;
        $tunjanganKehadiran = 0;

        if ($member->jenis === 'karyawan') {
            $jumlahAbsenTidakCuti = Absensi::where('member_id', $member->id)
                ->whereBetween('tanggal', [$periodeMulai->copy()->startOfDay(), $periodeSelesai->copy()->endOfDay()])
                ->mengurangiTunjangan()
                ->count();

            if ($jumlahAbsenTidakCuti >= 2) {
                $tunjanganKehadiran = 0;
            } elseif ($jumlahAbsenTidakCuti == 1) {
                $tunjanganKehadiran = (int) ($tunjanganKehadiranPenuh * 0.5);
            } else {
                $tunjanganKehadiran = $tunjanganKehadiranPenuh;
            }
        }

        $kas = AkunDetail::pluck('nama', 'id')->prepend('select kas', '')->toArray();
        return view('admin.penggajians.create', compact('member', 'kas', 'bagian', 'level', 'lamaKerja', 'tBagian', 'performance', 'transportasi', 'gaji', 'jmlLembur', 'totalLembur', 'totalKasbon', 'tunjanganKehadiran', 'jumlahAbsenTidakCuti', 'periodeMulai', 'periodeSelesai'));
    }

    public function store(Request $request)
    {
        $this->validateMemberModal($request, [
            'member_id' => 'required|exists:members,id',
            'akun_detail_id' => 'required|exists:akun_details,id',
        ]);

        DB::transaction(function () use($request) {
            //insert into penggajian
            penggajian::insert([
                'member_id' => $request->member_id,
                'bulan' => date('n'),
                'tahun' => date('Y'),
                'jam_lembur' => $request->jam_lembur,
                'pokok' => $request->pokok,
                'lembur' => $request->lembur,
                'kasbon' => $request->kasbon,
                'bonus' => $request->bonus,
                'total' => $request->total,
                'lama_kerja' => $request->lama_kerja,
                'bagian' => $request->bagian,
                'performance' => $request->performance,
                'transportasi' => $request->transportasi,
                'komunikasi' => $request->komunikasi,
                'kehadiran' => $request->kehadiran,
                'jumlah_lain' => $request->jumlah_lain,
                'lain_lain' => $request->lain_lain,
                'created_at' => Carbon::now(),
                'akun_detail_id' => $request->akun_detail_id,
            ]);

            //update kasbon
            $kasbon = Kasbon::where('member_id', '=', $request->member_id)->orderBy('id', 'DESC')->first();
            if (!empty($kasbon)) {
                $saldoAwal = $kasbon->saldo;
            } else {
                $saldoAwal = 0;
            }

            $data['pengeluaran'] = $request->kasbon;
            $data['member_id'] = $request->member_id;
            $data['created_at'] = Carbon::now();
            $data['keterangan'] = 'potong dari gaji';
            $data['saldo'] = $saldoAwal - $request->kasbon;

            //insert kasbon
            if ($saldoAwal != 0) {
                Kasbon::create($data);
            }

            // update lembur
            $lembur = Lembur::where([['member_id', '=', $request->member_id], ['dibayar', '=', 'belum']])->update([
                'dibayar' => 'sudah',
            ]);

            //get member
            $member = Member::find($request->member_id);

            //insert into buku besar table (saldo dihitung via BukuBesarService)
            BukuBesar::create([
                'akun_detail_id' => $request->akun_detail_id,
                'ket' => 'bayar gaji ke ' . $member->nama_lengkap,
                'kredit' => $request->total,
                'kode' => 'gji',
                'debet' => 0,
                'created_at' => Carbon::now(),
            ]);
        });

        return $this->memberModalResponse(
            $request,
            __('Penggajian berhasil ditambahkan.'),
            route('members.penggajian', $request->member_id)
        );
    }

    public function slip(Penggajian $penggajian)
    {
        return view('admin.penggajians.slip',compact('penggajian'));
    }

    public function createFreelance(Member $member)
    {
        $jmlLembur = Lembur::where([['member_id', '=', $member->id], ['dibayar', '=', 'belum']])->where('status', 'approved')->sum('jam');
        $totalLembur = $member->lembur * $jmlLembur;

        $tagihansBelumDibayar = FreelanceTagihan::where('member_id', $member->id)
            ->where('dibayar', 'belum')
            ->orderBy('tanggal')
            ->get();
        $jumlahHari = $tagihansBelumDibayar->count();
        $totalTagihan = $tagihansBelumDibayar->sum('nominal_upah');

        $kasbon = Kasbon::where('member_id', '=', $member->id)
            ->orderBy('id', 'DESC')
            ->first();
        $saldoKasbon = max(0, (int) ($kasbon->saldo ?? 0));
        $upahSebelumPotongan = (int) $totalTagihan + (int) $totalLembur;
        $totalKasbon = min($saldoKasbon, 500000, max(0, $upahSebelumPotongan));

        $kas = AkunDetail::pluck('nama', 'id')->prepend('select kas', '')->toArray();
        return view('admin.penggajians.createFreelance', compact(
            'member',
            'kas',
            'totalLembur',
            'jmlLembur',
            'tagihansBelumDibayar',
            'jumlahHari',
            'totalTagihan',
            'totalKasbon',
            'saldoKasbon'
        ));
    }

    public function storeFreelance(Request $request)
    {
        $this->validateMemberModal($request, [
            'member_id' => 'required|exists:members,id',
            'akun_detail_id' => 'required|exists:akun_details,id',
            'jumlah_hari' => 'required|integer|min:0',
            'jumlah_lain' => 'nullable|integer',
            'kasbon' => 'nullable|integer|min:0',
        ]);
        DB::transaction(function () use($request) {
            $member = Member::findOrFail($request->member_id);
            $kasbon = Kasbon::where('member_id', $member->id)->orderBy('id', 'DESC')->lockForUpdate()->first();
            $saldoAwal = max(0, (int) ($kasbon->saldo ?? 0));
            $potonganKasbon = (int) ($request->kasbon ?? 0);
            if ($potonganKasbon > $saldoAwal) {
                throw ValidationException::withMessages(['kasbon' => 'Potongan kasbon melebihi saldo kasbon saat ini.']);
            }

            $tagihansBelumDibayar = FreelanceTagihan::where('member_id', $member->id)
                ->where('dibayar', 'belum')->get();
            $jumlahHari = $tagihansBelumDibayar->isNotEmpty()
                ? $tagihansBelumDibayar->count()
                : (int) $request->jumlah_hari;
            $upah = $tagihansBelumDibayar->isNotEmpty()
                ? (int) $tagihansBelumDibayar->sum('nominal_upah')
                : (int) $member->upah * $jumlahHari;
            $jmlLembur = Lembur::where('member_id', $member->id)
                ->where('dibayar', 'belum')->where('status', 'approved')->sum('jam');
            $totalLembur = (int) ($member->lembur * $jmlLembur);
            $total = $upah + $totalLembur + (int) ($request->jumlah_lain ?? 0) - $potonganKasbon;
            if ($total < 0) {
                throw ValidationException::withMessages(['kasbon' => 'Potongan kasbon melebihi upah yang akan dibayar.']);
            }

            //insert into penggajian
            $penggajian = Penggajian::create([
                'member_id' => $member->id,
                'jam_lembur' => $jmlLembur,
                'lembur' => $totalLembur,
                'pokok' => $jumlahHari,
                'kasbon' => $potonganKasbon,
                'total' => $total,
                'jumlah_lain' => $request->jumlah_lain ?? 0,
                'lain_lain' => $request->lain_lain ?? null,
                'akun_detail_id' => $request->akun_detail_id,
            ]);

            FreelanceTagihan::where('member_id', $member->id)
                ->where('dibayar', 'belum')
                ->update(['dibayar' => 'sudah', 'penggajian_id' => $penggajian->id]);

            // update lembur
            Lembur::where([['member_id', '=', $member->id], ['dibayar', '=', 'belum']])->where('status', 'approved')->update([
                'dibayar' => 'sudah',
            ]);

            //update kasbon
            if ($potonganKasbon > 0) {
                Kasbon::create([
                    'pengeluaran' => $potonganKasbon,
                    'member_id' => $member->id,
                    'created_at' => Carbon::now(),
                    'keterangan' => 'potong dari gaji',
                    'saldo' => $saldoAwal - $potonganKasbon,
                ]);
            }

            //insert into buku besar table (saldo dihitung via BukuBesarService)
            BukuBesar::create([
                'akun_detail_id' => $request->akun_detail_id,
                'ket' => 'bayar gaji ke ' . $member->nama_lengkap,
                'kredit' => $total,
                'kode' => 'gji',
                'debet' => 0,
                'created_at' => Carbon::now(),
            ]);
        });

        return $this->memberModalResponse(
            $request,
            __('Penggajian berhasil ditambahkan.'),
            route('members.penggajianFreelance', $request->member_id)
        );
    }
}
