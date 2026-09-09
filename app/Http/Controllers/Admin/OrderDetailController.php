<?php

namespace App\Http\Controllers\Admin;

use Gate;
use App\Models\Chat;
use App\Models\Spek;
use App\Models\Order;
use App\Models\Member;
use App\Models\Gaji;
use App\Models\Produk;
use App\Models\Pemproses;
use App\Models\Produksi;
use App\Services\StokService;
use App\Models\OrderDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Intervention\Image\Facades\Image;
use Symfony\Component\HttpFoundation\Response;

class OrderDetailController extends Controller
{
    private function hasRoleInsensitive(string ...$names): bool
    {
        $normalized = collect($names)->map(fn ($name) => strtolower($name));

        return auth()->user()->roles->contains(
            fn ($role) => $normalized->contains(strtolower($role->name))
        );
    }

    private function isMarketingOnly(): bool
    {
        // Marketing sekarang termasuk role edit penuh (lihat order_edit_roles()).
        return false;
    }

    private function isProduksiLevel(): bool
    {
        return is_status_advance_only();
    }

    private function canEditOrderDetailAll(): bool
    {
        if (can_edit_order_role()) {
            return true;
        }

        $user = auth()->user();

        return $user->can('order_detail_edit') || $user->can('order_detail_create');
    }

    private function canEditOrderDetailLimited(): bool
    {
        return $this->canEditOrderDetailAll();
    }

    private function authorizeOrderDetailLimited(): void
    {
        abort_if(! $this->canEditOrderDetailLimited(), Response::HTTP_FORBIDDEN, '403 Forbidden');
    }

    private function authorizeOrderDetailAll(): void
    {
        abort_if(! $this->canEditOrderDetailAll(), Response::HTTP_FORBIDDEN, '403 Forbidden');
    }

    private function authorizeOrderDetailCabang(?Order $order): void
    {
        abort_unless_can_edit_cabang($order?->cabang_id);
    }

    private function authorizeOrderDetailCabangFromDetail(OrderDetail $detail): void
    {
        $detail->loadMissing('order');
        $this->authorizeOrderDetailCabang($detail->order);
    }

    private function canShowOrderHeaderActions(): bool
    {
        return can_edit_order_role()
            || ($this->canEditOrderDetailAll() && auth()->user()->can('order_detail_create'));
    }

    private function authorizeOrderDetailCreate(): void
    {
        abort_if(! $this->canShowOrderHeaderActions(), Response::HTTP_FORBIDDEN, '403 Forbidden');
    }

    private function orderDetailAccessFlags(?Order $order = null): array
    {
        $canEditCabang = $order
            ? can_edit_cabang_record($order->cabang_id)
            : can_edit_lintas_cabang();

        return [
            'canEditCabang' => $canEditCabang,
            'canEditAll' => $this->canEditOrderDetailAll() && $canEditCabang,
            'canEditLimited' => $this->canEditOrderDetailLimited() && $canEditCabang,
            'isMarketingOnly' => $this->isMarketingOnly(),
            'isProduksiLevel' => $this->isProduksiLevel(),
            'canShowOrderActions' => $this->canShowOrderHeaderActions() && $canEditCabang,
        ];
    }

    public function index(Order $order)
    {
        abort_if(Gate::denies('order_detail_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $order->load('projectMp', 'pemproses');

        $orderDetails = OrderDetail::where('order_id', $order->id)
            ->with(['produk.produkModel.kategori.kategoriUtama', 'spek', 'produksi', 'pemproses'])
            ->get();
        $produksi = Produksi::orderedForStatusSelect();
        $pemprosesUtama = Pemproses::utama()->orderBy('nama')->get();
        $pemprosesSetting = Pemproses::setting()->orderBy('nama')->get();
        $chats = Chat::with(['member', 'user'])
            ->where(function ($query) use ($order) {
                $query->where('order_id', $order->id);

                if ($order->projectMp) {
                    $query->orWhere('project_mp_id', $order->projectMp->id);
                }
            })
            ->orderBy('id')
            ->get();

        return view(
            'admin.orderDetails.index',
            array_merge(
                compact('orderDetails', 'order', 'produksi', 'pemprosesUtama', 'pemprosesSetting', 'chats'),
                $this->orderDetailAccessFlags($order)
            )
        );
    }

    public function create(Order $order)
    {
        $this->authorizeOrderDetailCreate();
        $this->authorizeOrderDetailCabang($order);

        $speks = Spek::all();
        return view('admin.orderDetails.create', compact('order', 'speks'));
    }

    public function store(Request $request)
    {
        $this->authorizeOrderDetailCreate();

        $request->validate([
            'produk_id' => 'required',
            'harga' => 'required',
            'jumlah' => 'required',
            'deathline' => 'required',
        ]);

        $order = Order::findOrFail($request->order_id);
        $this->authorizeOrderDetailCabang($order);
        $produksi = Produksi::initialStatus();

        //insert project detail
        $dataDetail['order_id'] = $request->order_id;
        $dataDetail['produk_id'] = $request->produk_id;
        $dataDetail['tema'] = $request->tema;
        $dataDetail['jumlah'] = $request->jumlah;
        $dataDetail['harga'] = $request->harga;
        $dataDetail['keterangan'] = $request->keterangan;
        $dataDetail['produksi_id'] = $produksi?->id;
        $dataDetail['deathline'] = $request->deathline;
        $dataDetail['nota'] = $request->nota;
        $dataDetail['created_at'] = Carbon::now();

        $produk = Produk::find($request->produk_id);
        $dataDetail['hpp'] = $produk->hpp;

        $orderDetail = OrderDetail::create($dataDetail);

        $speks = Spek::all();

        $sync = [];
        foreach ($speks as $spek) {
            if ($request->{$spek->nama}) {
                $sync[$spek->id] = ['keterangan' => $request->{$spek->nama}];
            }
        }
        $orderDetail->spek()->sync($sync);
        return redirect('/admin/order/' . $request->order_id . '/detail')->withSuccess(__('Order Detail created successfully.'));
    }

    private function orderDetailRedirectUrl(OrderDetail $orderDetail): string
    {
        $projectMp = $orderDetail->order?->projectMp;

        if ($projectMp) {
            return route('projectmp.detail', $projectMp->id);
        }

        return route('order.detail', $orderDetail->order->id);
    }

    public function gambar(OrderDetail $detail)
    {
        $this->authorizeOrderDetailAll();
        $this->authorizeOrderDetailCabangFromDetail($detail);
        abort_if(Gate::denies('order_detail_create'), Response::HTTP_FORBIDDEN, '403 Forbidden');
        return view('admin.orderDetails.gambar', compact('detail'));
    }

    public function upload(Request $request)
    {
        $this->authorizeOrderDetailAll();

        $request->validate([
            'gambar' => 'required|mimes:jpeg,png,jpg',
        ]);

        $orderDetail = OrderDetail::find($request->order_detail_id);
        $this->authorizeOrderDetailCabangFromDetail($orderDetail);
        $gambar = null;
        if ($request->hasFile('gambar')) {
            $img = $request->file('gambar');
            $filename = time() . '.' . $request->gambar->extension();
            $img_resize = Image::make($img->getRealPath());
            $img_resize->resize(500, null, function ($constraint) {
                $constraint->aspectRatio();
            });
            $save_path = public_path('uploads/order/');
            if (!file_exists($save_path)) {
                try {
                    mkdir($save_path, 0777, true);
                } catch (\Exception $e) {
                    throw new \Exception('Unable to create directory. Please check folder permissions.');
                }
            }
            $img_resize->save($save_path . $filename);
            $gambar = $filename;
        }

        $orderDetail->update([
            'gambar' => $gambar,
        ]);

        return redirect($this->orderDetailRedirectUrl($orderDetail))->withSuccess(__('Gambar detail updated successfully.'));
    }

    public function updateStatus(Request $request, OrderDetail $detail)
    {
        abort_if($this->isMarketingOnly(), Response::HTTP_FORBIDDEN, '403 Forbidden');
        $this->authorizeOrderDetailLimited();
        $this->authorizeOrderDetailCabangFromDetail($detail);

        $produksiId = (int) $request->produksi_id;
        if (! $this->isAllowedProduksiStatus($detail, $produksiId)) {
            $message = __('Status tidak sesuai alur produksi.');

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['message' => $message], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            return redirect()->back()->withErrors($message);
        }

        $this->applyProduksiStatus($detail, $produksiId);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['message' => __('Status updated successfully.')]);
        }

        return redirect()->back()->withSuccess(__('Status updated successfully.'));
    }

    public function advanceStatus(Request $request, OrderDetail $detail)
    {
        abort_if(Gate::denies('order_detail_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');
        abort_if(! $this->isProduksiLevel(), Response::HTTP_FORBIDDEN, '403 Forbidden');
        $this->authorizeOrderDetailCabangFromDetail($detail);

        $nextProduksi = $detail->produksi?->nextInFlow($detail);

        if (! $nextProduksi) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['message' => __('Tidak ada proses selanjutnya.')], 422);
            }

            return redirect()->back()->withErrors(__('Tidak ada proses selanjutnya.'));
        }

        $this->applyProduksiStatus($detail, $nextProduksi->id);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'message' => __('Status updated successfully.'),
                'produksi' => $nextProduksi->nama,
            ]);
        }

        return redirect()->back()->withSuccess(__('Status updated successfully.'));
    }

    private function isAllowedProduksiStatus(OrderDetail $detail, int $produksiId): bool
    {
        $allowedIds = Produksi::statusPathForDetail($detail)->pluck('id');

        if ($detail->produksi_id) {
            $allowedIds->push($detail->produksi_id);
        }

        return $allowedIds->unique()->contains($produksiId);
    }

    private function applyProduksiStatus(OrderDetail $detail, int $produksiId): void
    {
        DB::transaction(function () use ($detail, $produksiId) {
            $detail->loadMissing(['produk.produkModel', 'order.kontak']);

            $from = Produksi::find($detail->produksi_id);
            $to = Produksi::find($produksiId);

            if (Produksi::produkTracksStock($detail) && $from && $to) {
                if ($detail->order->konsumen_detail) {
                    $username = '('.$detail->order->konsumen_detail.')';
                } else {
                    $username = '';
                }

                $stokService = app(StokService::class);
                $cabangId = resolve_cabang_id($detail->order->cabang_id ?? $detail->order->kontak?->cabang_id);

                if (Produksi::shouldDeductStock($from, $to)) {
                    $stokService->kurang(
                        $detail->produk->id,
                        $cabangId,
                        $detail->jumlah,
                        'jual',
                        'barang dijual ke ' . $detail->order->kontak->nama . ' ' . $username,
                        $detail->order->id,
                        [],
                        false
                    );
                }

                if (Produksi::shouldRestoreStock($from, $to)) {
                    $stokService->tambah(
                        $detail->produk->id,
                        $cabangId,
                        $detail->jumlah,
                        'btl',
                        'barang dikembalikan dari ' . $detail->order->kontak->nama . ' ' . $username,
                        $detail->order->id
                    );
                }
            }

            $detail->update([
                'produksi_id' => $produksiId,
                'hpp' => $detail->produk->hpp,
            ]);
        });
    }

    public function updatePemproses(Request $request, OrderDetail $detail)
    {
        abort_if($this->isMarketingOnly(), Response::HTTP_FORBIDDEN, '403 Forbidden');
        $this->authorizeOrderDetailLimited();
        $this->authorizeOrderDetailCabangFromDetail($detail);

        $request->validate([
            'pemproses_id' => [
                'nullable',
                'exists:pemproses,id',
                function ($attribute, $value, $fail) {
                    if ($value && !Pemproses::setting()->where('id', $value)->exists()) {
                        $fail(__('Label harus kategori setting.'));
                    }
                },
            ],
        ]);

        $detail->update([
            'pemproses_id' => $request->pemproses_id ?: null,
        ]);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['message' => __('Pemproses updated successfully.')]);
        }

        return redirect('/admin/order/' . $detail->order->id . '/detail')->withSuccess(__('Pemproses updated successfully.'));
    }

    public function edit(OrderDetail $detail)
    {
        $this->authorizeOrderDetailLimited();
        $this->authorizeOrderDetailCabangFromDetail($detail);

        $speks = Spek::all();

        return view(
            'admin.orderDetails.edit',
            array_merge(compact('detail', 'speks'), $this->orderDetailAccessFlags($detail->order))
        );
    }

    public function update(Request $request, $detail)
    {
        $this->authorizeOrderDetailLimited();

        $orderDetail = OrderDetail::find($detail);
        $this->authorizeOrderDetailCabangFromDetail($orderDetail);

        if ($this->isMarketingOnly()) {
            $produk = $request->produk_id ?: $orderDetail->produk_id;
            $orderDetail->update([
                'produk_id' => $produk,
                'tema' => $request->tema,
                'keterangan' => $request->keterangan,
                'deathline' => $request->deathline,
            ]);

            $speks = Spek::all();
            $sync = [];
            foreach ($speks as $spek) {
                if ($request->{$spek->nama}) {
                    $sync[$spek->id] = ['keterangan' => $request->{$spek->nama}];
                }
            }
            $orderDetail->spek()->sync($sync);

            return redirect('/admin/order/' . $orderDetail->order->id . '/detail')
                ->withSuccess(__('Order Detail updated successfully.'));
        }

        abort_if(! $this->canEditOrderDetailAll(), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $produk = $request->produk_id ? $request->produk_id : $orderDetail->produk_id;
        $orderDetail->update([
            'produk_id' => $produk,
            'tema' => $request->tema,
            'jumlah' => $request->jumlah,
            'harga' => $request->harga,
            'keterangan' => $request->keterangan,
            'deathline' => $request->deathline,
        ]);
        $speks = Spek::all();

        $sync = [];
        foreach ($speks as $spek) {
            if ($request->{$spek->nama}) {
                $sync[$spek->id] = ['keterangan' => $request->{$spek->nama}];
            }
        }
        $orderDetail->spek()->sync($sync);

        return redirect('/admin/order/' . $orderDetail->order->id . '/detail')
            ->withSuccess(__('Order Detail updated successfully.'));
    }

    public function editGambar(OrderDetail $detail)
    {
        $this->authorizeOrderDetailAll();
        $this->authorizeOrderDetailCabangFromDetail($detail);

        return view('admin.orderDetails.editGambar', compact('detail'));
    }

    public function updateGambar(Request $request)
    {
        $this->authorizeOrderDetailAll();

        $request->validate([
            'gambar' => 'required|mimes:jpeg,png,jpg',
        ], [
            'gambar.required' => 'Pilih file gambar terlebih dahulu.',
            'gambar.mimes' => 'Gambar harus berformat JPEG, PNG, atau JPG.',
        ]);

        $orderDetail = OrderDetail::find($request->order_detail_id);
        $this->authorizeOrderDetailCabangFromDetail($orderDetail);
        $gambar = null;
        if ($request->hasFile('gambar')) {
            $img = $request->file('gambar');
            $filename = time() . '.' . $request->gambar->extension();
            $img_resize = Image::make($img->getRealPath());
            $img_resize->resize(500, null, function ($constraint) {
                $constraint->aspectRatio();
            });
            $save_path = public_path('uploads/order/');
            if (!file_exists($save_path)) {
                try {
                    mkdir($save_path, 0777, true);
                } catch (\Exception $e) {
                    throw new \Exception('Unable to create directory. Please check folder permissions.');
                }
            }
            $img_resize->save($save_path . $filename);
            $gambar = $filename;
        }

        if ($orderDetail->gambar) {
            unlink("uploads/order/" . $orderDetail->gambar);
        }

        $orderDetail->update([
            'gambar' => $gambar,
        ]);

        $redirectUrl = $this->orderDetailRedirectUrl($orderDetail);
        $message = __('Gambar detail updated successfully.');

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['message' => $message]);
        }

        return redirect($redirectUrl)->withSuccess($message);
    }
}
