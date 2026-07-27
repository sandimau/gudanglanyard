<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AkunDetail;
use App\Models\AkunKategori;
use App\Models\BukuBesar;
use App\Services\BukuBesarService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AkunDetailController extends Controller
{

    public function index()
    {
        $akunDetails = AkunDetail::with(['akun_kategori'])->get();

        return view('admin.akundetails.index', compact('akunDetails'));
    }

    public function kas()
    {
        $akunDetails = AkunDetail::with(['akun_kategori'])
            ->whereHas('akun_kategori', function ($q) {
                $q->whereIn('id', [1, 8]); // Kas categories
            })
            ->get();

        return view('admin.akundetails.kas', compact('akunDetails'));
    }

    public function create()
    {
        $akun_kategoris = AkunKategori::pluck('nama', 'id');

        return view('admin.akundetails.create', compact('akun_kategoris'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'nama' => 'required',
            'akun_kategori_id' => 'required',
        ]);

        $akunDetail = AkunDetail::create($request->all());

        return redirect()->route('akunDetails.index')->withSuccess(__('Kas created successfully.'));
    }

    public function edit(AkunDetail $akunDetail)
    {
        $akun_kategoris = AkunKategori::pluck('nama', 'id')->prepend(trans('pilih akun kategori'), '');

        $akunDetail->load('akun_kategori');

        return view('admin.akundetails.edit', compact('akunDetail', 'akun_kategoris'));
    }

    public function update(Request $request, AkunDetail $akunDetail)
    {
        $akunDetail->update($request->all());

        return redirect()->route('akunDetails.index')->withSuccess(__('Kas updated successfully.'));
    }

    public function show(AkunDetail $akunDetail)
    {
        $akunDetail->load('akun_kategori');

        return view('admin.akundetails.show', compact('akunDetail'));
    }

    public function destroy(AkunDetail $akunDetail)
    {
        $akunDetail->delete();

        return back();
    }

    public function bukubesar(AkunDetail $akunDetail)
    {
        $saldo = app(BukuBesarService::class)->saldoTersedia($akunDetail->id);
        $bukubesars = BukuBesar::saldoAkun(['saldo' => $saldo])
            ->with('user')
            ->where('buku_besars.akun_detail_id', $akunDetail->id)
            ->orderBy('buku_besars.id', 'desc')
            ->paginate(15);

        return view('admin.akundetails.bukubesar', compact('bukubesars', 'akunDetail'));
    }

    public function transfer(Request $request, AkunDetail $akunDetail)
    {
        $kas = AkunDetail::where('id', '!=', $akunDetail->id)->pluck('nama', 'id')->toArray();
        return view('admin.akundetails.transfer', compact('akunDetail', 'kas'));
    }

    public function transferStore(Request $request)
    {
        $request->validate([
            'akun_detail_tujuan' => 'required',
            'jumlah' => 'required',
        ]);

        DB::transaction(function () use ($request) {
            $akunDetailDari = akunDetail::find($request->akun_detail_dari);
            $akunDetailKe = akunDetail::find($request->akun_detail_tujuan);

            //insert into buku besar table dari
            BukuBesar::create([
                'akun_detail_id' => $request->akun_detail_dari,
                'kode' => 'trf',
                'ket' => 'transfer ke ' . $akunDetailKe->nama. ' (' . $request->keterangan . ')',
                'kredit' => $request->jumlah,
                'debet' => 0,
            ]);

            //insert into buku besar table tujuan
            BukuBesar::create([
                'akun_detail_id' => $request->akun_detail_tujuan,
                'kode' => 'trf',
                'ket' => 'transfer dari ' . $akunDetailDari->nama. ' (' . $request->keterangan . ')',
                'kredit' => 0,
                'debet' => $request->jumlah,
            ]);
        });

        return redirect()->route('akunDetails.index')->withSuccess(__('Kas created successfully.'));
    }

    public function transferLain(Request $request, AkunDetail $akunDetail)
    {
        return view('admin.akundetails.transferLain', compact('akunDetail'));
    }

    public function transferStoreLain(Request $request)
    {
        $request->validate([
            'jumlah' => 'required',
            'keterangan' => 'required',
        ]);

        DB::transaction(function () use ($request) {
            //insert into buku besar table dari
            BukuBesar::create([
                'akun_detail_id' => $request->akun_detail_id,
                'kode' => 'lain',
                'ket' => $request->keterangan,
                'kredit' => 0,
                'debet' => $request->jumlah,
            ]);
        });

        return redirect()->route('akunDetails.index')->withSuccess(__('Kas created successfully.'));
    }
}
