<?php

namespace App\Http\Controllers\Admin;

use Gate;
use App\Models\Order;
use App\Models\Produk;
use App\Models\ProdukStok;
use App\Services\StokService;
use Illuminate\Http\Request;
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

        return view('admin.produkStoks.create', compact('produk'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'tambah' => 'required',
            'kurang' => 'required',
            'keterangan' => 'required',
            'tanggal' => 'required',
        ]);

        app(StokService::class)->opname(
            $request->produk_id,
            resolve_cabang_id(),
            (int) $request->tambah,
            (int) $request->kurang,
            $request->keterangan,
            [
                'created_at' => $request->tanggal,
                'user_id' => auth()->user()->id,
            ]
        );

        return redirect()->route('produkStok.index', $request->produk_id)->withSuccess(__('Produk Stok berhasil diupdate'));
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
