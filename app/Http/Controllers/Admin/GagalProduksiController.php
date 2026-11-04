<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GagalProduksi;
use App\Models\Order;
use App\Models\ProjectMp;
use App\Models\Produk;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class GagalProduksiController extends Controller
{
    public function index(Request $request)
    {
        abort_if(Gate::denies('gagal_produksi_access'), 403);

        $data = $request->validate(['tanggal' => 'nullable|date_format:Y-m-d']);
        $tanggal = $data['tanggal'] ?? now()->toDateString();

        $query = GagalProduksi::query()->whereDate('tanggal', $tanggal);
        $cabangFilter = cabang_id() ?: (can_edit_lintas_cabang() ? null : member_cabang_id());
        if ($cabangFilter) {
            $query->where(function ($query) use ($cabangFilter) {
                $query->where('cabang_id', $cabangFilter)
                    ->orWhere(function ($lama) use ($cabangFilter) {
                        $lama->whereNull('cabang_id')->whereHas('orderDetail', function ($detail) use ($cabangFilter) {
                            $detail->withTrashed()->whereHas('order', fn ($order) => $order->where('orders.cabang_id', $cabangFilter));
                        });
                    });
            });
        } elseif (!can_edit_lintas_cabang()) {
            $query->whereRaw('1 = 0');
        }

        $totalGagal = (clone $query)->sum('jumlah');
        $totalKerugian = (clone $query)->sum('kerugian');
        $catatan = $query->with(['orderDetail.produk.produkModel', 'orderDetail.order', 'projectMp', 'user'])
            ->orderByDesc('id')
            ->paginate(30)
            ->withQueryString();

        return view('admin.gagalProduksi.index', compact('catatan', 'tanggal', 'totalGagal', 'totalKerugian'));
    }

    public function storeOrder(Request $request, Order $order)
    {
        $this->authorizeWriter();
        abort_unless(can_edit_cabang_record($order->cabang_id), 403);

        $data = $request->validate($this->rules('order_detail_id'));
        $detail = $order->orderDetail()->with('produk.produkModel.kategori', 'produksi')->findOrFail($data['order_detail_id']);
        abort_unless($detail->produk && $detail->produk->produkModel?->kategori, 422);

        $this->saveFailure($data, $detail->produk, resolve_cabang_id($order->cabang_id), $detail->produksi?->nama, [
            'order_detail_id' => $detail->id,
        ]);

        return redirect()->to(route('order.detail', $order->id) . '#gagal-produksi')
            ->with('success', 'Produk gagal berhasil dicatat.');
    }

    public function storeProjectMp(Request $request, ProjectMp $projectMp)
    {
        $this->authorizeWriter();
        abort_unless(can_edit_cabang_record($projectMp->cabang_id), 403);

        $data = $request->validate($this->rules('sumber'));
        if (preg_match('/^project:(\d+)$/', $data['sumber'], $matches)) {
            $detail = $projectMp->details()->with('produk.produkModel.kategori', 'produksi')->findOrFail($matches[1]);
            $links = ['project_mp_id' => $projectMp->id, 'project_mp_detail_id' => $detail->id];
        } elseif (preg_match('/^order:(\d+)$/', $data['sumber'], $matches) && $projectMp->order) {
            $detail = $projectMp->order->orderDetail()->with('produk.produkModel.kategori', 'produksi')->findOrFail($matches[1]);
            $links = ['project_mp_id' => $projectMp->id, 'order_detail_id' => $detail->id];
        } else {
            return back()->withErrors(['sumber' => 'Pilih produk dari order ini.'])->withInput();
        }

        abort_unless($detail->produk && $detail->produk->produkModel?->kategori, 422);
        $this->saveFailure($data, $detail->produk, resolve_cabang_id($projectMp->cabang_id), $detail->produksi?->nama, $links);

        return redirect()->to(route('projectmp.detail', $projectMp->id) . '#gagal-produksi')
            ->with('success', 'Produk gagal berhasil dicatat.');
    }

    private function rules(string $detailField): array
    {
        return [
            $detailField => $detailField === 'sumber'
                ? ['required', 'string', 'regex:/^(project|order):[0-9]+$/']
                : ['required', 'integer'],
            'tahap' => 'nullable|string|max:255',
            'tanggal' => 'required|date_format:Y-m-d',
            'jumlah' => 'required|integer|min:1|max:1000000000',
            'keterangan' => 'nullable|string|max:2000',
        ];
    }

    private function saveFailure(array $data, Produk $produk, int $cabangId, ?string $tahap, array $links): void
    {
        $hpp = max(0, (int) $produk->hpp);

        GagalProduksi::create(array_merge([
            'produk_id' => $produk->id,
            'nama_produk' => mb_substr($produk->nama_lengkap, 0, 255),
            'cabang_id' => $cabangId,
            'tanggal' => $data['tanggal'],
            'jumlah' => $data['jumlah'],
            'hpp_satuan' => $hpp,
            'kerugian' => $data['jumlah'] * $hpp,
            'tahap' => $data['tahap'] ?? $tahap,
            'keterangan' => $data['keterangan'] ?? null,
            'user_id' => auth()->id(),
        ], $links));
    }

    public function destroy(GagalProduksi $gagalProduksi)
    {
        $this->authorizeWriter();
        $cabang = $gagalProduksi->cabang_id ?? $gagalProduksi->orderDetail?->order?->cabang_id;
        abort_unless(can_edit_cabang_record($cabang), 403);
        $tanggal = $gagalProduksi->tanggal->toDateString();
        $gagalProduksi->delete();

        return redirect()->route('gagalProduksi.index', ['tanggal' => $tanggal])
            ->with('success', 'Catatan produk gagal berhasil dihapus.');
    }

    private function authorizeWriter(): void
    {
        abort_if(Gate::denies('gagal_produksi_access') || Gate::denies('gagal_produksi_manage'), 403);
    }
}
