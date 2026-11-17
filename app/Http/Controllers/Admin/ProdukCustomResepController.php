<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Produk;
use App\Models\ProdukCustomResep;
use App\Models\ProdukModel;
use App\Services\ProdukCustomService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class ProdukCustomResepController extends Controller
{
    private function produk(Produk $produk): Produk
    {
        abort_unless(Gate::allows('produk_access'), 403);
        abort_unless(app(ProdukCustomService::class)->isCustom($produk->id), 404);
        return $produk->load('produkModel.kategori');
    }

    public function index(Request $request, Produk $produk)
    {
        $produk = $this->produk($produk);
        $reseps = ProdukCustomResep::with(['komponenModel.produk' => fn ($query) => $query->where('status', 1)])
            ->where('produk_id', $produk->id)->orderBy('id')->get();
        $editResep = $request->query('edit') ? $reseps->firstWhere('id', $request->query('edit')) : null;
        if ($request->query('edit')) abort_unless($editResep, 404);
        $komponenId = old('komponen_model_id', $editResep?->komponen_model_id);
        $komponenPilihan = $komponenId ? ProdukModel::with('kategori')->find($komponenId) : null;
        $skuOptions = app(ProdukCustomService::class)->skuOptions($produk->id);
        return view('produkModel.resep', compact('produk', 'reseps', 'editResep', 'komponenId', 'komponenPilihan', 'skuOptions'));
    }

    public function apiKomponen(Request $request, Produk $produk)
    {
        $this->produk($produk);
        $request->validate(['q' => 'nullable|string|max:100']);
        $query = trim((string) $request->query('q', ''));
        if ($query === '') {
            return response()->json([]);
        }

        $komponens = ProdukModel::with('kategori')
            ->where('stok', 1)
            ->whereKeyNot($produk->produk_model_id)
            ->whereHas('produk', fn ($q) => $q->where('status', 1))
            ->where(function ($q) use ($query) {
                $q->where('nama', 'like', '%' . $query . '%')
                    ->orWhereHas('kategori', fn ($kategori) => $kategori->where('nama', 'like', '%' . $query . '%'))
                    ->orWhereHas('produk', fn ($varian) => $varian->where('nama', 'like', '%' . $query . '%'));
            })
            ->orderBy('nama')->limit(20)->get();

        return response()->json($komponens->map(fn ($model) => [
            'id' => $model->id,
            'label' => $model->kategori?->nama . ' - ' . $model->nama,
        ])->values());
    }

    public function store(Request $request, Produk $produk)
    {
        $this->produk($produk);
        $data = $this->validatedData($request, $produk);
        $produk->customReseps()->create($data);
        $produk->update(['hpp' => app(ProdukCustomService::class)->estimasiHpp($produk->id)]);
        return back()->withSuccess('Baris resep ditambahkan.');
    }

    public function update(Request $request, Produk $produk, ProdukCustomResep $resep)
    {
        $this->produk($produk);
        abort_unless($resep->produk_id === $produk->id, 404);
        $resep->update($this->validatedData($request, $produk));
        $produk->update(['hpp' => app(ProdukCustomService::class)->estimasiHpp($produk->id)]);
        return redirect()->route('produkCustom.resep', $produk)->withSuccess('Baris resep diperbarui.');
    }

    private function validatedData(Request $request, Produk $produk): array
    {
        $data = $request->validate([
            'jenis' => 'required|in:bahan,internal',
            'komponen_model_id' => 'required_if:jenis,bahan|nullable|exists:produk_models,id',
            'jumlah' => 'required|numeric|min:0.01',
            'biaya' => 'required_if:jenis,internal|nullable|integer|min:0',
            'per_order' => 'nullable|boolean',
            'keterangan' => 'nullable|string|max:255',
        ]);
        if ($data['jenis'] === 'bahan') {
            $model = ProdukModel::findOrFail($data['komponen_model_id']);
            if ((int) $model->stok !== 1 || (int) $model->id === (int) $produk->produk_model_id
                || ! $model->produk()->where('status', 1)->exists()) {
                throw ValidationException::withMessages(['komponen_model_id' => 'Bahan harus produk berstok lain yang memiliki varian aktif.']);
            }
            $data['biaya'] = null;
            $data['keterangan'] = null;
            $data['per_order'] = false;
        } else {
            $data['komponen_model_id'] = null;
            $data['per_order'] = $request->boolean('per_order');
            if ($data['per_order']) $data['jumlah'] = 1;
        }
        return $data;
    }

    public function destroy(Produk $produk, ProdukCustomResep $resep)
    {
        $this->produk($produk);
        abort_unless($resep->produk_id === $produk->id, 404);
        $resep->delete();
        $produk->update(['hpp' => app(ProdukCustomService::class)->estimasiHpp($produk->id)]);
        return back()->withSuccess('Baris resep dihapus.');
    }
}
