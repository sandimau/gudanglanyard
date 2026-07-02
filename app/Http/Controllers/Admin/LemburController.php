<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\RespondsToMemberModal;
use App\Http\Controllers\Controller;
use App\Models\Lembur;
use App\Models\Member;
use Illuminate\Http\Request;

class LemburController extends Controller
{
    use RespondsToMemberModal;
    // Helper untuk nama bulan
    protected function getBulans()
    {
        return [
            1 => 'Januari',
            2 => 'Februari',
            3 => 'Maret',
            4 => 'April',
            5 => 'Mei',
            6 => 'Juni',
            7 => 'Juli',
            8 => 'Agustus',
            9 => 'September',
            10 => 'Oktober',
            11 => 'November',
            12 => 'Desember',
        ];
    }

    public function create(Member $member)
    {
        $bulans = $this->getBulans();
        return view('admin.lemburs.create', compact('member', 'bulans'));
    }

    public function store(Request $request)
    {
        $this->validateMemberModal($request, [
            'member_id' => 'required|exists:members,id',
            'keterangan' => 'required|string',
            'jam' => 'required|numeric|min:0.5',
        ]);

        Lembur::create([
            'tahun' => date("Y"),
            'bulan' => now()->month,
            'keterangan' => $request->keterangan,
            'jam' => $request->jam,
            'member_id' => $request->member_id,
            'dibayar' => 'belum',
            'status' => 'waiting',
        ]);

        return $this->memberModalResponse(
            $request,
            __('Lembur berhasil ditambahkan.'),
            route('members.lembur', $request->member_id)
        );
    }

    public function edit(Lembur $lembur)
    {
        if (in_array($lembur->status, ['approved', 'rejected'])) {
            return $this->memberModalResponse(
                request(),
                __('Lembur yang sudah disetujui atau ditolak tidak dapat diedit.'),
                route('members.lembur', $lembur->member_id)
            );
        }

        $bulans = $this->getBulans();
        return view('admin.lemburs.edit', compact('lembur', 'bulans'));
    }

    public function update(Request $request, Lembur $lembur)
    {
        if (in_array($lembur->status, ['approved', 'rejected'])) {
            return $this->memberModalResponse(
                $request,
                __('Lembur yang sudah disetujui atau ditolak tidak dapat diedit.'),
                route('members.lembur', $lembur->member_id)
            );
        }

        $this->validateMemberModal($request, [
            'keterangan' => 'required|string',
            'jam' => 'required|numeric|min:0.5',
            'member_id' => 'required|exists:members,id',
        ]);

        $lembur->update([
            'jam' => $request->jam,
            'keterangan' => $request->keterangan,
        ]);

        return $this->memberModalResponse(
            $request,
            __('Lembur berhasil diperbarui.'),
            route('members.lembur', $request->member_id)
        );
    }

    public function approve(Lembur $lembur)
    {
        $lembur->update([
            'status' => 'approved',
        ]);

        return $this->memberModalResponse(
            request(),
            __('Lembur berhasil disetujui.'),
            route('members.lembur', $lembur->member_id)
        );
    }

    public function reject(Lembur $lembur)
    {
        $lembur->update([
            'status' => 'rejected',
        ]);

        return $this->memberModalResponse(
            request(),
            __('Lembur berhasil ditolak.'),
            route('members.lembur', $lembur->member_id)
        );
    }
}
