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

        // Tunjangan kehadiran berdasarkan absensi
        // Cuti = aman. Sakit/ijin/terlambat/alpha = mengurangi
        // 1x tidak masuk = 50%, 2x+ = hangus
        $tunjanganKehadiranPenuh = $level->kehadiran ?? 150000;
        $jumlahAbsenTidakCuti = Absensi::where('member_id', $member->id)
            ->whereMonth('tanggal', date('n'))
            ->whereYear('tanggal', date('Y'))
            ->whereIn('jenis', Absensi::jenisYangMengurangiTunjangan())
            ->count();
        if ($jumlahAbsenTidakCuti >= 2) {
            $tunjanganKehadiran = 0;
        } elseif ($jumlahAbsenTidakCuti == 1) {
            $tunjanganKehadiran = (int) ($tunjanganKehadiranPenuh * 0.5);
        } else {
            $tunjanganKehadiran = $tunjanganKehadiranPenuh;
        }

        $kas = AkunDetail::pluck('nama', 'id')->prepend('select kas', '')->toArray();
        return view('admin.penggajians.create', compact('member', 'kas', 'bagian', 'level', 'lamaKerja', 'tBagian', 'performance', 'transportasi', 'gaji', 'jmlLembur', 'totalLembur', 'totalKasbon', 'tunjanganKehadiran', 'jumlahAbsenTidakCuti'));
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

            //update saldo akun detail
            $akunDetail = AkunDetail::where('id', $request->akun_detail_id)->first();
            $saldo = $akunDetail->saldo;
            $update = $saldo - $request->total;
            $akunDetail->update([
                'saldo' => $update,
            ]);

            //get member
            $member = Member::find($request->member_id);

            //insert into buku besar table
            BukuBesar::insert([
                'akun_detail_id' => $request->akun_detail_id,
                'ket' => 'bayar gaji ke ' . $member->nama_lengkap,
                'kredit' => $request->total,
                'kode' => 'gji',
                'debet' => 0,
                'saldo' => $update,
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

        $kas = AkunDetail::pluck('nama', 'id')->prepend('select kas', '')->toArray();
        return view('admin.penggajians.createFreelance', compact(
            'member',
            'kas',
            'totalLembur',
            'jmlLembur',
            'tagihansBelumDibayar',
            'jumlahHari',
            'totalTagihan'
        ));
    }

    public function storeFreelance(Request $request)
    {
        $this->validateMemberModal($request, [
            'member_id' => 'required|exists:members,id',
            'akun_detail_id' => 'required|exists:akun_details,id',
            'jumlah_hari' => 'required|numeric|min:0',
        ]);
        DB::transaction(function () use($request) {
            //insert into penggajian
            $penggajian = Penggajian::create([
                'member_id' => $request->member_id,
                'jam_lembur' => $request->jam_lembur ?? 0,
                'lembur' => $request->lembur ?? 0,
                'pokok' => $request->jumlah_hari ?? 0,
                'total' => $request->total,
                'jumlah_lain' => $request->jumlah_lain ?? 0,
                'lain_lain' => $request->lain_lain ?? null,
                'akun_detail_id' => $request->akun_detail_id,
            ]);

            FreelanceTagihan::where('member_id', $request->member_id)
                ->where('dibayar', 'belum')
                ->update(['dibayar' => 'sudah', 'penggajian_id' => $penggajian->id]);

            // update lembur
            Lembur::where([['member_id', '=', $request->member_id], ['dibayar', '=', 'belum']])->update([
                'dibayar' => 'sudah',
            ]);

            //update saldo akun detail
            $akunDetail = AkunDetail::where('id', $request->akun_detail_id)->first();
            $saldo = $akunDetail->saldo;
            $update = $saldo - $request->total;
            $akunDetail->update([
                'saldo' => $update,
            ]);

            //get member
            $member = Member::find($request->member_id);

            //insert into buku besar table
            BukuBesar::insert([
                'akun_detail_id' => $request->akun_detail_id,
                'ket' => 'bayar gaji ke ' . $member->nama_lengkap,
                'kredit' => $request->total,
                'kode' => 'gji',
                'debet' => 0,
                'saldo' => $update,
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
