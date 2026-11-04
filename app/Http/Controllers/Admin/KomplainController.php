<?php

namespace App\Http\Controllers\Admin;

use App\Models\Komplain;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Produksi;
use App\Models\ProjectMpDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use App\Http\Controllers\Controller;
use Symfony\Component\HttpFoundation\Response;

class KomplainController extends Controller
{
    private function canCreateKomplain(): bool
    {
        $user = auth()->user();

        if (! $user) {
            return false;
        }

        return $user->can('komplain_access')
            || can_edit_order_role()
            || $user->can('order_detail_access')
            || $user->can('marketplace_access');
    }

    private function authorizeKomplainCabang(?int $cabangId): void
    {
        abort_unless_can_edit_cabang($cabangId);
    }

    public function index(Request $request)
    {
        abort_if(Gate::denies('komplain_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $baseQuery = Komplain::query();

        $status = $request->get('status');
        $tipe = $request->get('tipe');
        $search = trim((string) $request->get('q'));

        $komplains = (clone $baseQuery)
            ->with(['produk', 'order', 'orderDetail', 'projectMp', 'projectMpDetail', 'kontak', 'user', 'approvedBy'])
            ->when($status, fn ($q) => $q->where('status', $status))
            ->when($tipe, fn ($q) => $q->where('tipe', $tipe))
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('nama_konsumen', 'like', '%' . $search . '%')
                        ->orWhere('nota', 'like', '%' . $search . '%')
                        ->orWhere('bagian_salah', 'like', '%' . $search . '%')
                        ->orWhere('keterangan', 'like', '%' . $search . '%')
                        ->orWhereHas('produk', fn ($produk) => $produk->where('nama', 'like', '%' . $search . '%'));
                });
            })
            ->latest()
            ->paginate(25)
            ->withQueryString();

        $stats = [
            'pending' => (clone $baseQuery)->where('status', Komplain::STATUS_PENDING)->count(),
            'approved' => (clone $baseQuery)->where('status', Komplain::STATUS_APPROVED)->count(),
            'rejected' => (clone $baseQuery)->where('status', Komplain::STATUS_REJECTED)->count(),
            'kerugian' => (clone $baseQuery)->where('status', Komplain::STATUS_APPROVED)->sum('kerugian'),
        ];

        return view('admin.komplains.index', compact('komplains', 'stats', 'status', 'tipe', 'search'));
    }

    public function show(Komplain $komplain)
    {
        abort_if(Gate::denies('komplain_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $komplain->load(['produk', 'order', 'orderDetail', 'projectMp', 'projectMpDetail', 'kontak', 'user', 'approvedBy', 'replacementOrderDetail', 'replacementProjectMpDetail']);

        return view('admin.komplains.show', compact('komplain'));
    }

    public function store(Request $request)
    {
        abort_if(! $this->canCreateKomplain(), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $data = $request->validate([
            'komplain_target' => 'required|in:order_detail,project_mp_detail',
            'detail_id' => 'required|integer',
            'tipe' => 'nullable|in:offline,online',
            'bagian_salah' => 'required|string|max:255',
            'keterangan' => 'required|string|max:2000',
            'jumlah' => 'required|integer|min:1',
        ]);

        if ($data['komplain_target'] === 'order_detail') {
            $detail = OrderDetail::with(['order.kontak', 'produk'])->findOrFail($data['detail_id']);
            $order = $detail->order;

            abort_if(! $order, Response::HTTP_NOT_FOUND, 'Order tidak ditemukan.');
            $this->authorizeKomplainCabang($order->cabang_id);

            $maxJumlah = (int) $detail->jumlah;
            $this->validateJumlah($data['jumlah'], $maxJumlah);

            $isOnline = $order->marketplace !== null || $order->projectMp()->exists();

            if (isset($data['tipe'])) {
                $isOnline = $data['tipe'] === 'online';
            }

            $komplain = Komplain::create([
                'cabang_id' => $order->cabang_id,
                'order_id' => $order->id,
                'order_detail_id' => $detail->id,
                'project_mp_id' => $order->projectMp?->id,
                'produk_id' => $detail->produk_id,
                'kontak_id' => $order->kontak_id,
                'nama_konsumen' => $order->kontak->nama ?? '-',
                'nota' => $order->nota,
                'tipe' => $isOnline ? 'online' : 'offline',
                'jumlah' => $data['jumlah'],
                'bagian_salah' => $data['bagian_salah'],
                'keterangan' => $data['keterangan'],
                'status' => Komplain::STATUS_PENDING,
                'user_id' => auth()->id(),
            ]);
        } else {
            $detail = ProjectMpDetail::with(['projectMp', 'produk'])->findOrFail($data['detail_id']);
            $projectMp = $detail->projectMp;

            abort_if(! $projectMp, Response::HTTP_NOT_FOUND, 'Project marketplace tidak ditemukan.');
            $this->authorizeKomplainCabang($projectMp->cabang_id);

            $maxJumlah = (int) $detail->jumlah;
            $this->validateJumlah($data['jumlah'], $maxJumlah);

            $order = $projectMp->order;

            $komplain = Komplain::create([
                'cabang_id' => $projectMp->cabang_id,
                'order_id' => $order?->id,
                'order_detail_id' => null,
                'project_mp_id' => $projectMp->id,
                'project_mp_detail_id' => $detail->id,
                'produk_id' => $detail->produk_id,
                'kontak_id' => $order?->kontak_id,
                'nama_konsumen' => $projectMp->konsumen ?: ($order?->kontak?->nama ?? '-'),
                'nota' => $projectMp->nota,
                'tipe' => 'online',
                'jumlah' => $data['jumlah'],
                'bagian_salah' => $data['bagian_salah'],
                'keterangan' => $data['keterangan'],
                'status' => Komplain::STATUS_PENDING,
                'user_id' => auth()->id(),
            ]);
        }

        return redirect()->back()->withSuccess(__('Komplain berhasil dibuat dan menunggu persetujuan.'));
    }

    public function approve(Request $request, Komplain $komplain)
    {
        abort_if(Gate::denies('komplain_approve'), Response::HTTP_FORBIDDEN, '403 Forbidden');
        $this->authorizeKomplainCabang($komplain->cabang_id);

        if (! $komplain->is_pending) {
            return redirect()->back()->withErrors(__('Komplain ini sudah diproses.'));
        }

        $komplain->loadMissing(['orderDetail.spek', 'projectMpDetail', 'produk']);

        if (! $komplain->order_detail_id && ! $komplain->project_mp_detail_id) {
            return redirect()->back()->withErrors(__('Detail produk komplain tidak ditemukan.'));
        }

        try {
            $kerugian = DB::transaction(function () use ($komplain) {
                $sumber = $komplain->order_detail_id
                    ? $komplain->orderDetail
                    : $komplain->projectMpDetail;

                if (! $sumber) {
                    throw ValidationException::withMessages([
                        'komplain' => __('Produk asal komplain sudah tidak tersedia.'),
                    ]);
                }

                $hpp = $komplain->hpp_satuan;
                $jumlah = (int) $komplain->jumlah;
                $kerugian = $hpp * $jumlah;
                $produksi = Produksi::initialStatus();
                $keterangan = trim(
                    'Penggantian komplain: ' . $komplain->bagian_salah .
                    ($komplain->keterangan ? ' - ' . $komplain->keterangan : '')
                );

                $update = [
                    'status' => Komplain::STATUS_APPROVED,
                    'kerugian' => $kerugian,
                    'approved_by' => auth()->id(),
                    'approved_at' => now(),
                ];

                if ($komplain->order_detail_id) {
                    $order = $sumber->order;

                    $replacement = OrderDetail::create([
                        'order_id' => $sumber->order_id,
                        'komplain_id' => $komplain->id,
                        'produk_id' => $komplain->produk_id ?: $sumber->produk_id,
                        'tema' => $sumber->tema,
                        'jumlah' => $jumlah,
                        'harga' => 0,
                        'hpp' => $hpp,
                        'keterangan' => $keterangan,
                        'produksi_id' => $produksi?->id,
                        'deathline' => $sumber->deathline ?: $order?->deathline,
                        'nota' => $sumber->nota ?: $komplain->nota,
                        'pemproses_id' => $sumber->pemproses_id,
                        'created_at' => now(),
                    ]);

                    $sync = $sumber->spek->mapWithKeys(
                        fn ($spek) => [$spek->id => ['keterangan' => $spek->pivot->keterangan]]
                    )->all();
                    $replacement->spek()->sync($sync);

                    $update['replacement_order_detail_id'] = $replacement->id;
                } else {
                    $replacement = ProjectMpDetail::create([
                        'project_id' => $sumber->project_id,
                        'komplain_id' => $komplain->id,
                        'produk_id' => $komplain->produk_id ?: $sumber->produk_id,
                        'tema' => $sumber->tema,
                        'jumlah' => $jumlah,
                        'harga' => 0,
                        'hpp' => $hpp,
                        'keterangan' => $keterangan,
                        'nota' => $sumber->nota ?: $komplain->nota,
                        'produksi_id' => $produksi?->id,
                        'pemproses_id' => $sumber->pemproses_id,
                        'deadline' => $sumber->deadline,
                    ]);

                    $update['replacement_project_mp_detail_id'] = $replacement->id;
                }

                $komplain->update($update);

                return $kerugian;
            });
        } catch (ValidationException $e) {
            return redirect()->back()->withErrors($e->errors());
        }

        $message = $kerugian > 0
            ? __('Komplain disetujui. Produk pengganti ditambahkan dan kerugian dicatat sebesar Rp ') . number_format($kerugian, 0, ',', '.')
            : __('Komplain disetujui dan produk pengganti ditambahkan. HPP belum tersedia, jadi kerugian tercatat Rp 0.');

        return redirect()->route('komplain.index')->withSuccess($message);
    }

    public function reject(Request $request, Komplain $komplain)
    {
        abort_if(Gate::denies('komplain_approve'), Response::HTTP_FORBIDDEN, '403 Forbidden');
        $this->authorizeKomplainCabang($komplain->cabang_id);

        $request->validate([
            'catatan_admin' => 'nullable|string|max:1000',
        ]);

        if (! $komplain->is_pending) {
            return redirect()->back()->withErrors(__('Komplain ini sudah diproses.'));
        }

        $komplain->update([
            'status' => Komplain::STATUS_REJECTED,
            'catatan_admin' => $request->catatan_admin,
        ]);

        return redirect()->route('komplain.index')->withSuccess(__('Komplain ditolak.'));
    }

    private function validateJumlah(int $jumlah, int $maxJumlah): void
    {
        if ($maxJumlah > 0 && $jumlah > $maxJumlah) {
            throw ValidationException::withMessages([
                'jumlah' => __('Jumlah komplain tidak boleh melebihi jumlah produk pada detail order (' . $maxJumlah . ').'),
            ]);
        }
    }
}
