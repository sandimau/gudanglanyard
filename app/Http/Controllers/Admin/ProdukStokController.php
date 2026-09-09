<?php

namespace App\Http\Controllers\Admin;

use Gate;
use App\Models\Order;
use App\Models\Produk;
use App\Models\ProdukStok;
use App\Services\StokService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Symfony\Component\HttpFoundation\Response;

class ProdukStokController extends Controller
{
    public function index(Produk $produk)
    {
        abort_if(Gate::denies('produk_stok_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $cabangId = resolve_cabang_id();
        $saldo = app(StokService::class)->saldoTersedia($produk->id, $cabangId);
        $produkStoks = ProdukStok::saldoStok(['saldo' => $saldo, 'cabang_id' => $cabangId])
            ->where('produk_stoks.produk_id', $produk->id)
            ->where('produk_stoks.cabang_id', $cabangId)
            ->orderBy('produk_stoks.id', 'desc')
            ->get();

        return view('admin.produkStoks.index', compact('produkStoks', 'produk'));
    }

    public function create(Produk $produk)
    {
        abort_if(Gate::denies('produk_stok_create'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $produk->load('produkModel.kategori');
        $cabangId = resolve_cabang_id();
        $stokService = app(StokService::class);

        $varians = Produk::where('produk_model_id', $produk->produk_model_id)
            ->orderByRaw("CASE WHEN nama IS NULL OR nama = '' THEN 1 ELSE 0 END")
            ->orderBy('nama')
            ->get()
            ->map(function (Produk $varian) use ($stokService, $cabangId) {
                $varian->stok_saat_ini = $stokService->saldoTersedia($varian->id, $cabangId);

                return $varian;
            });

        return view('admin.produkStoks.create', compact('produk', 'varians'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'produk_id' => 'required|exists:produks,id',
            'keterangan' => 'required',
            'tanggal' => 'required',
            'varians' => 'required|array|min:1',
            'varians.*.tambah' => 'nullable|integer|min:0',
            'varians.*.kurang' => 'nullable|integer|min:0',
        ]);

        $produk = Produk::findOrFail($request->produk_id);
        $varianIds = Produk::where('produk_model_id', $produk->produk_model_id)->pluck('id');

        $mutasi = collect($request->varians)
            ->filter(function ($item, $id) use ($varianIds) {
                if (!$varianIds->contains((int) $id)) {
                    return false;
                }

                return ((int) ($item['tambah'] ?? 0) > 0) || ((int) ($item['kurang'] ?? 0) > 0);
            });

        if ($mutasi->isEmpty()) {
            return back()
                ->withInput()
                ->withErrors(['varians' => 'Isi tambah atau kurang minimal untuk satu varian.']);
        }

        $cabangId = resolve_cabang_id();
        $stokService = app(StokService::class);
        $extra = [
            'created_at' => $request->tanggal,
            'user_id' => auth()->user()->id,
        ];

        DB::transaction(function () use ($mutasi, $stokService, $cabangId, $request, $extra) {
            foreach ($mutasi as $produkId => $item) {
                $stokService->opname(
                    (int) $produkId,
                    $cabangId,
                    (int) ($item['tambah'] ?? 0),
                    (int) ($item['kurang'] ?? 0),
                    $request->keterangan,
                    $extra
                );
            }
        });

        $produk->loadMissing('produkModel');

        if ($varianIds->count() > 1) {
            return redirect()
                ->route('produkModel.index', $produk->produkModel->kategori_id)
                ->withSuccess(__('Produk Stok berhasil diupdate'));
        }

        return redirect()
            ->route('produk.stok', $produk->id)
            ->withSuccess(__('Produk Stok berhasil diupdate'));
    }

    public function opname(Request $request)
    {
        $dari = null;
        $sampai = null;

        $query = ProdukStok::saldoBerjalan()
            ->with('produk')
            ->where('produk_stoks.kode', 'opn')
            ->whereHas('produk');

        if ($request->bulan) {
            $dari = $request->bulan . '-01';
            $sampai = date('Y-m-t', strtotime($request->bulan));
        } elseif ($request->dari && $request->sampai) {
            $dari = $request->dari;
            $sampai = $request->sampai;
        }

        if ($dari && $sampai) {
            $query->whereBetween('produk_stoks.created_at', [$dari, $sampai]);
        }

        if ($request->produk_id) {
            $query->where('produk_stoks.produk_id', $request->produk_id);
        }

        $produkStoks = $query
            ->orderBy('produk_stoks.id', 'desc')
            ->paginate(10)
            ->appends($request->only(['dari', 'sampai', 'produk_id', 'bulan']));

        return view('admin.produkStoks.opname', compact('produkStoks', 'dari', 'sampai'));
    }

    public function editStore(ProdukStok $produkStok)
    {
        abort_if(Gate::denies('opname_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        if ($produkStok->detail_id) {
            $order = Order::find($produkStok->detail_id);
            $ket = 'barang dikembalikan dari ' .$order->kontak->nama.' '.$order->konsumen_detail .' ('.$order->nota.')';
            $detail_id = $produkStok->detail_id;
        } else {
            $ket = $produkStok->keterangan;
            if (strpos($ket, 'oleh') !== false) {
                $parts = explode('oleh', $ket, 2);
                $afterOleh = trim($parts[1]);
                $firstWord = strtok($afterOleh, " ");
                $ket = $firstWord;
                $order = Order::where('konsumen_detail', $ket)->first();
                $ket = 'barang dikembalikan dari ' .$order->kontak->nama.' '.$order->konsumen_detail .' ('.$order->nota.')';
                $detail_id = $order->id;
            }
        }

        app(StokService::class)->tambah(
            $produkStok->produk_id,
            resolve_cabang_id($produkStok->cabang_id),
            $produkStok->kurang,
            'btl',
            $ket,
            $detail_id
        );

        $produkStok->update([
            'status' => 'manual',
        ]);

        return redirect()->route('produk.stok', ['produk' => $produkStok->produk_id]);
    }
}
