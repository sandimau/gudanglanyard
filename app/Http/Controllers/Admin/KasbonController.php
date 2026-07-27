<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\RespondsToMemberModal;
use App\Models\Kasbon;
use App\Models\Member;
use App\Models\BukuBesar;
use App\Models\AkunDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;

class KasbonController extends Controller
{
    use RespondsToMemberModal;
    public function create(Member $member)
    {
        $kas = AkunDetail::pluck('nama', 'id')->toArray();
        return view('admin.kasbons.create', compact('member', 'kas'));
    }

    public function store(Request $request)
    {
        $this->validateMemberModal($request, [
            'member_id' => 'required|exists:members,id',
            'tanggal' => 'required|date',
            'jumlah' => 'required|numeric|min:1',
            'keterangan' => 'required|string',
            'akun_detail_id' => 'required|exists:akun_details,id',
        ]);

        DB::transaction(function () use ($request) {
            //get kasbon sebelumnya
            $kasbon = Kasbon::where('member_id', $request->member_id)->orderBy('id', 'DESC')->first();
            if (!empty($kasbon)) {
                $saldoAwal = $kasbon->saldo;
            } else {
                $saldoAwal = 0;
            }

            $data['pemasukan'] = $request->jumlah;
            $data['keterangan'] = $request->keterangan;
            $data['member_id'] = $request->member_id;
            $data['created_at'] = $request->tanggal;
            $data['saldo'] = $saldoAwal + $request->jumlah;

            //insert kasbon
            Kasbon::create($data);

            //get nama member untuk ket kasbon
            $member = Member::where('id', $request->member_id)->first();

            //insert into buku besar table (saldo dihitung via BukuBesarService)
            BukuBesar::create([
                'akun_detail_id' => $request->akun_detail_id,
                'ket' => 'kasbon ke ' . $member->nama_lengkap,
                'kredit' => $request->jumlah,
                'debet' => 0,
                'created_at' => Carbon::now(),
            ]);
        });

        return $this->memberModalResponse(
            $request,
            __('Kasbon berhasil ditambahkan.'),
            route('members.kasbon', $request->member_id)
        );
    }

    public function bayar(Member $member)
    {
        $members = $member;
        $kas = AkunDetail::pluck('nama', 'id')->toArray();
        $kasbon = Kasbon::where('member_id',$members->id)->first();
        return view('admin.kasbons.bayar', compact('members', 'kas','kasbon'));
    }

    public function bayarStore(Request $request)
    {
        $this->validateMemberModal($request, [
            'member_id' => 'required|exists:members,id',
            'tanggal' => 'required|date',
            'jumlah' => 'required|numeric|min:1',
            'keterangan' => 'required|string',
            'akun_detail_id' => 'required|exists:akun_details,id',
        ]);

        DB::transaction(function () use ($request) {
            //get kasbon sebelumnya
            $kasbon = Kasbon::where('member_id', $request->member_id)->orderBy('id', 'DESC')->first();
            if (!empty($kasbon)) {
                $saldoAwal = $kasbon->saldo;
            } else {
                $saldoAwal = 0;
            }

            $data['pengeluaran'] = $request->jumlah;
            $data['keterangan'] = $request->keterangan;
            $data['member_id'] = $request->member_id;
            $data['created_at'] = $request->tanggal;
            $data['saldo'] = $saldoAwal - $request->jumlah;

            //insert kasbon
            Kasbon::create($data);

            //get nama member untuk ket kasbon
            $member = Member::where('id', $request->member_id)->first();

            //insert into buku besar table (saldo dihitung via BukuBesarService)
            BukuBesar::create([
                'akun_detail_id' => $request->akun_detail_id,
                'ket' => 'bayar kasbon dari ' . $member->nama_lengkap,
                'kredit' => 0,
                'debet' => $request->jumlah,
                'created_at' => Carbon::now(),
            ]);
        });

        return $this->memberModalResponse(
            $request,
            __('Pembayaran kasbon berhasil dicatat.'),
            route('members.kasbon', $request->member_id)
        );
    }
}
