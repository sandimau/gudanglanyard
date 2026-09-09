<?php

namespace App\Http\Controllers\Admin;

use App\Models\Cabang;
use App\Models\Kontak;
use App\Models\Produk;
use App\Models\ProdukModel;
use Illuminate\Http\Request;
use App\Models\ProdukKategori;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\File;
use Intervention\Image\Facades\Image;
use Illuminate\Support\Facades\Storage;

class ProdukModelController extends Controller
{
    protected $satuan = [
        'Pcs',
        'Box',
        'Lusin',
        'Pack',
        'Kg',
        'Gram',
        'Liter',
        'Meter',
        'Roll',
        'Unit',
        'Set',
        'Karton'
    ];

    public function index()
    {
        $currentUrl = request()->fullUrl();
        $lastNumber = preg_replace('/[^0-9]/', '', substr($currentUrl, strrpos($currentUrl, '?') + 1));
        $kategori = ProdukKategori::find($lastNumber);
        $cabangs = Cabang::aktif()->orderBy('nama')->get();

        $produks = Produk::select(
            'produks.id as produk_id',
            'produks.nama as varian',
            'produks.hpp as hpp',
            'produk_models.nama as model',
            'produk_models.harga',
            'produk_models.satuan',
            'produk_models.deskripsi',
            'produk_models.jual',
            'produk_models.beli',
            'produk_models.stok',
            'produk_models.id as model_id',
            'produk_models.gambar',
            'produk_models.kategori_id',
            'produk_models.kontak_id',
            'belanja_details.harga as harga_beli'
        )
            ->join('produk_models', 'produks.produk_model_id', '=', 'produk_models.id')
            ->leftJoin('belanja_details', function ($join) {
                $join->on('produks.id', '=', 'belanja_details.produk_id')
                    ->whereRaw('belanja_details.id = (SELECT id FROM belanja_details WHERE produk_id = produks.id ORDER BY id DESC LIMIT 1)');
            })
            ->where('produk_models.kategori_id', $kategori->id)
            ->get();

        $stokByProdukCabang = $this->loadStokSemuaCabang(
            $produks->pluck('produk_id')->map(fn ($id) => (int) $id)->all(),
            $cabangs->pluck('id')->map(fn ($id) => (int) $id)->all()
        );

        return view('produkModel.index', compact('produks', 'kategori', 'cabangs', 'stokByProdukCabang'));
    }

    /**
     * Saldo stok per produk per cabang (tahun lalu + mutasi tahun berjalan).
     *
     * @param  array<int>  $produkIds
     * @param  array<int>  $cabangIds
     * @return array<int, array<int, int>>
     */
    private function loadStokSemuaCabang(array $produkIds, array $cabangIds): array
    {
        $result = [];
        foreach ($produkIds as $produkId) {
            foreach ($cabangIds as $cabangId) {
                $result[$produkId][$cabangId] = 0;
            }
        }

        if ($produkIds === [] || $cabangIds === []) {
            return $result;
        }

        $tahun = (int) date('Y');
        $awalTahun = $tahun . '-01-01 00:00:00';
        $awalTahunDepan = ($tahun + 1) . '-01-01 00:00:00';

        $saldoLalu = DB::table('produk_last_stoks as pls')
            ->join(DB::raw('(
                SELECT produk_id, cabang_id, MAX(tahun) as max_tahun
                FROM produk_last_stoks
                WHERE tahun < ' . $tahun . '
                  AND produk_id IN (' . implode(',', $produkIds) . ')
                  AND cabang_id IN (' . implode(',', $cabangIds) . ')
                GROUP BY produk_id, cabang_id
            ) as sub'), function ($join) {
                $join->on('pls.produk_id', '=', 'sub.produk_id')
                    ->on('pls.cabang_id', '=', 'sub.cabang_id')
                    ->on('pls.tahun', '=', 'sub.max_tahun');
            })
            ->select('pls.produk_id', 'pls.cabang_id', 'pls.saldo')
            ->get();

        foreach ($saldoLalu as $row) {
            $result[(int) $row->produk_id][(int) $row->cabang_id] = (int) $row->saldo;
        }

        $mutasi = DB::table('produk_stoks')
            ->select(
                'produk_id',
                'cabang_id',
                DB::raw('COALESCE(SUM(COALESCE(tambah, 0) - COALESCE(kurang, 0)), 0) as saldo')
            )
            ->whereIn('produk_id', $produkIds)
            ->whereIn('cabang_id', $cabangIds)
            ->whereNull('deleted_at')
            ->where('created_at', '>=', $awalTahun)
            ->where('created_at', '<', $awalTahunDepan)
            ->groupBy('produk_id', 'cabang_id')
            ->get();

        foreach ($mutasi as $row) {
            $produkId = (int) $row->produk_id;
            $cabangId = (int) $row->cabang_id;
            $result[$produkId][$cabangId] = ($result[$produkId][$cabangId] ?? 0) + (int) $row->saldo;
        }

        return $result;
    }

    public function create()
    {
        $kategori = ProdukKategori::find(request()->input('kategori_id'));
        $kontaks = Kontak::all();
        $satuan = $this->satuan;
        return view('produkModel.create', compact('kategori', 'kontaks', 'satuan'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama' => 'required',
            'harga' => 'required|numeric',
            'satuan' => 'required',
            'deskripsi' => 'nullable',
            'gambar' => 'nullable|image|mimes:jpeg,png,jpg',
        ]);

        $gambar = null;
        if ($request->hasFile('gambar')) {
            $img = $request->file('gambar');
            $filename = time() . '.' . $request->gambar->extension();
            $img_resize = Image::make($img->getRealPath());
            $img_resize->resize(500, null, function ($constraint) {
                $constraint->aspectRatio();
            });
            $save_path = 'uploads/produk/';
            if (!file_exists($save_path)) {
                mkdir($save_path, 666, true);
            }
            $img_resize->save(public_path($save_path . $filename));
            $gambar = $filename;
        }

        $kategori = ProdukKategori::find($request->kategori_id);

        $validated['kategori_id'] = $request->kategori_id;
        $validated['jual'] = $kategori->kategoriUtama->jual;
        $validated['beli'] = $kategori->kategoriUtama->beli;
        $validated['stok'] = $kategori->kategoriUtama->stok;
        $validated['produksi'] = $kategori->kategoriUtama->produksi;
        $validated['gambar'] = $gambar;

        $produkModel = ProdukModel::create($validated);

        // Tambahkan data ke tabel produk
        Produk::create([
            'status' => 1,
            'produk_model_id' => $produkModel->id
        ]);
        return redirect()->route('produkModel.index', ['kategori_id' => $kategori->id])->with('success', 'Produk berhasil ditambahkan');
    }

    public function edit(ProdukModel $produkModel)
    {
        $kategori = ProdukKategori::find($produkModel->kategori_id);
        $kontaks = Kontak::all();
        $satuan = $this->satuan;
        return view('produkModel.edit', [
            'produkModel' => $produkModel,
            'kategori' => $kategori,
            'kontaks' => $kontaks,
            'satuan' => $satuan
        ]);
    }

    public function update(Request $request, ProdukModel $produkModel)
    {
        $validated = $request->validate([
            'nama' => 'required',
            'harga' => 'required|numeric',
            'satuan' => 'required',
            'deskripsi' => 'nullable',
            'gambar' => 'nullable|image|mimes:jpeg,png,jpg',
        ]);

        $gambar = $produkModel->gambar;
        if ($request->hasFile('gambar')) {
            $img = $request->file('gambar');
            $filename = time() . '.' . $request->gambar->extension();
            $img_resize = Image::make($img->getRealPath());
            $img_resize->resize(500, null, function ($constraint) {
                $constraint->aspectRatio();
            });
            $save_path = 'uploads/produk/';
            if (!file_exists($save_path)) {
                mkdir($save_path, 666, true);
            }
            $img_resize->save(public_path($save_path . $filename));
            $gambar = $filename;
        }

        $kategori = ProdukKategori::find($request->kategori_id);

        $validated['kategori_id'] = $request->kategori_id;
        $validated['jual'] = $kategori->kategoriUtama->jual;
        $validated['beli'] = $kategori->kategoriUtama->beli;
        $validated['stok'] = $kategori->kategoriUtama->stok;
        $validated['gambar'] = $gambar;
        $produkModel->update($validated);
        return redirect()->route('produkModel.index', ['kategori_id' => $kategori->id])->with('success', 'Produk berhasil diperbarui');
    }

    public function destroy(ProdukModel $produkModel)
    {
        if ($produkModel->gambar) {
            Storage::delete('public/' . $produkModel->gambar);
        }
        $produk->delete();
        return redirect()->route('produkModel.index')->with('success', 'Produk berhasil dihapus');
    }

    public function show(ProdukModel $produkModel)
    {
        return view('produkModel.show', compact('produkModel'));
    }
}
