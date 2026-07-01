<?php

namespace App\Http\Controllers\Admin;

use App\Models\Hutang;
use App\Models\Kontak;
use App\Models\BukuBesar;
use App\Models\AkunDetail;
use App\Models\HutangDetail;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class HutangController extends Controller
{
    public function index(Request $request)
    {
        $jenis = $request->get('jenis', 'hutang');
        $status = $request->get('status');

        $query = Hutang::with(['kontak', 'akun_detail', 'details'])->latest();

        if ($jenis === 'piutang') {
            $query->where('jenis', 'piutang');
        } else {
            $query->whereIn('jenis', ['hutang', 'belanja', 'belanja produksi']);
            $jenis = 'hutang';
        }

        if ($jenis === 'hutang' && $status === 'lunas') {
            $query->whereRaw('jumlah <= (SELECT COALESCE(SUM(jumlah), 0) FROM hutang_details WHERE hutang_details.hutang_id = hutangs.id)');
        } elseif ($jenis === 'hutang' && $status === 'belum_lunas') {
            $query->whereRaw('jumlah > (SELECT COALESCE(SUM(jumlah), 0) FROM hutang_details WHERE hutang_details.hutang_id = hutangs.id)');
        }

        $hutangs = $query->paginate(10)->appends($request->query());

        return view('admin.hutang.index', compact('hutangs', 'jenis', 'status'));
    }

    public function create()
    {
        $kontaks = Kontak::all();
        $jenis = request()->jenis;
        $kas = AkunDetail::kas()->get();

        return view('admin.hutang.create', compact('kontaks', 'jenis', 'kas'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'kontak_id' => 'required',
            'akun_detail_id' => 'required',
            'tanggal' => 'required|date',
            'jumlah' => 'required|numeric',
            'keterangan' => 'nullable|string',
            'jenis' => 'required|in:hutang,piutang',
        ]);

        $hutang = Hutang::create($validated);

        $debet = $validated['jenis'] == 'hutang' ? $validated['jumlah'] : 0;
        $kredit = $validated['jenis'] == 'hutang' ? 0 : $validated['jumlah'];
        $keterangan = $validated['jenis'] == 'hutang' ? 'Hutang dari ' . $hutang->kontak->nama : 'Piutang ke ' . $hutang->kontak->nama;

        BukuBesar::create([
            'akun_detail_id' => $validated['akun_detail_id'],
            'kode' => $validated['jenis'] == 'hutang' ? 'htg' : 'ptg',
            'debet' => $debet,
            'kredit' => $kredit,
            'ket' => $keterangan,
            'detail_id' => $hutang->id,
        ]);

        return redirect()->route('hutang.index')
            ->with('success', request()->jenis == 'hutang' ? 'Hutang berhasil ditambahkan' : 'Piutang berhasil ditambahkan');
    }

    public function bayar(Hutang $hutang)
    {
        $hutang->load(['kontak', 'details.akun_detail']);
        $kas = AkunDetail::kas()->get();

        return view('admin.hutang.bayar', compact('hutang', 'kas'));
    }

    public function bayarStore(Request $request)
    {
        $validated = $request->validate([
            'hutang_id' => 'required',
            'akun_detail_id' => 'required',
            'tanggal' => 'required|date',
            'jumlah' => 'required|numeric',
            'keterangan' => 'required|string',
        ]);

        $hutang = Hutang::find($validated['hutang_id']);

        HutangDetail::create($validated);

        if (request()->jenis == 'belanja') {
            $keterangan = 'Bayar Belanja ke ' . $hutang->kontak->nama;
            $kode = 'blj';
            $debet = 0;
            $kredit = $validated['jumlah'];
        }

        if ($request->jenis == 'hutang') {
            $keterangan = 'Bayar Hutang ke ' . $hutang->kontak->nama;
            $kode = 'htg';
            $debet = 0;
            $kredit = $validated['jumlah'];
        }

        if ($request->jenis == 'piutang') {
            $keterangan = 'Bayar Piutang dari ' . $hutang->kontak->nama;
            $kode = 'ptg';
            $debet = $validated['jumlah'];
            $kredit = 0;
        }

        BukuBesar::create([
            'akun_detail_id' => $validated['akun_detail_id'],
            'kode' => $kode,
            'debet' => $debet,
            'kredit' => $kredit,
            'ket' => $keterangan,
            'detail_id' => $request->jenis == 'belanja' ? $hutang->detail_id : $hutang->id,
        ]);

        $message = match ($request->jenis) {
            'piutang' => 'Piutang berhasil dibayar',
            'hutang' => 'Hutang berhasil dibayar',
            default => 'Pembayaran berhasil disimpan',
        };

        return redirect()->route('hutang.bayar', $hutang)->with('success', $message);
    }

    public function detail(Hutang $hutang)
    {
        $hutang->load(['kontak', 'details.akun_detail']);

        return view('admin.hutang.detail', compact('hutang'));
    }
}
