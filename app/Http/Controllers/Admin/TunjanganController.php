<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\RespondsToMemberModal;
use App\Http\Controllers\Controller;
use App\Models\AkunDetail;
use App\Models\BukuBesar;
use App\Models\Member;
use App\Models\Tunjangan;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class TunjanganController extends Controller
{
    use RespondsToMemberModal;
    public function create(Member $member)
    {
        $kas = AkunDetail::pluck('nama', 'id')->toArray();
        return view('admin.tunjangans.create', compact('member', 'kas'));
    }

    public function store(Request $request)
    {
        $this->validateMemberModal($request, [
            'member_id' => 'required|exists:members,id',
            'tanggal' => 'required|date',
            'ket' => 'required|string',
            'jumlah' => 'required|numeric|min:1',
            'akun_detail_id' => 'required|exists:akun_details,id',
        ]);

        DB::transaction(function () use ($request) {
            //get tunjangan sebelumnya
            $tunjangan = Tunjangan::where('member_id', $request->member_id)->whereYear('created_at', '=', Carbon::now()->year)
                ->orderBy('id', 'DESC')->first();
            isset($tunjangan->saldo) ? $saldo = $tunjangan->saldo : $saldo = 0;
            $total = $saldo + request()->jumlah;

            //insert data
            $data['member_id'] = $request->member_id;
            $data['jumlah'] = $request->jumlah;
            $data['created_at'] = $request->tanggal;
            $data['akun_detail_id'] = $request->akun_detail_id;
            $data['ket'] = $request->ket;
            $data['saldo'] = $total;
            Tunjangan::create($data);

            //get nama member untuk ket gaji
            $member = Member::where('id', $request->member_id)->first();

            //insert into buku besar table (saldo dihitung via BukuBesarService)
            BukuBesar::create([
                'akun_detail_id' => $request->akun_detail_id,
                'ket' => 'tunjangan ke ' . $member->nama_lengkap,
                'kredit' => $request->jumlah,
                'debet' => 0,
                'created_at' => Carbon::now(),
            ]);
        });

        return $this->memberModalResponse(
            $request,
            __('Tunjangan berhasil ditambahkan.'),
            route('members.tunjangan', $request->member_id)
        );
    }
}

