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
        return $this->hasRoleInsensitive('marketing')
            && ! $this->hasRoleInsensitive('supervisor', 'super', 'manager');
    }

    private function isProduksiLevel(): bool
    {
        if ($this->hasRoleInsensitive('supervisor', 'super', 'manager')) {
            return false;
        }

        if ($this->hasRoleInsensitive('produksi')) {
            return true;
        }

        $member = Member::where('user_id', auth()->id())->first();
        if (! $member) {
            return false;
        }

        $gaji = Gaji::with(['bagian', 'level'])->where('member_id', $member->id)->orderByDesc('id')->first();
        $bagianNama = strtolower($gaji?->bagian?->nama ?? '');
        $levelNama = strtolower($gaji?->level?->nama ?? '');

        return $bagianNama === 'produksi' || $levelNama === 'produksi';
    }

    private function canEditOrderDetailAll(): bool
    {
        if ($this->isMarketingOnly()) {
            return false;
        }

        $user = auth()->user();

        return $this->hasRoleInsensitive('supervisor', 'super', 'manager')
            || $user->can('order_detail_edit')
            || $user->can('order_detail_create');
    }

    private function canEditOrderDetailLimited(): bool
    {
        if ($this->isMarketingOnly()) {
            return true;
        }

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

    private function canShowOrderHeaderActions(): bool
    {
        if ($this->isMarketingOnly()) {
            return true;
        }

        $user = auth()->user();

        return $this->canEditOrderDetailAll() && $user->can('order_detail_create');
    }

    private function authorizeOrderDetailCreate(): void
    {
        abort_if(! $this->canShowOrderHeaderActions(), Response::HTTP_FORBIDDEN, '403 Forbidden');
    }

    private function orderDetailAccessFlags(): array
    {
        return [
            'canEditAll' => $this->canEditOrderDetailAll(),
            'canEditLimited' => $this->canEditOrderDetailLimited(),
            'isMarketingOnly' => $this->isMarketingOnly(),
            'isProduksiLevel' => $this->isProduksiLevel(),
            'canShowOrderActions' => $this->canShowOrderHeaderActions(),
        ];
    }

    public function index(Order $order)
    {
        abort_if(Gate::denies('order_detail_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $orderDetails = OrderDetail::where('order_id', $order->id)
            ->with(['produk.produkModel.kategori.kategoriUtama', 'spek', 'produksi', 'pemproses'])
            ->get();
        $produksi = Produksi::orderedForStatusSelect();
        $pemproses = Pemproses::orderBy('nama')->get();
        $chats = Chat::where('order_id', $order->id)
            ->with(['member', 'user'])
            ->get();

        return view(
            'admin.orderDetails.index',
            array_merge(
                compact('orderDetails', 'order', 'produksi', 'pemproses', 'chats'),
                $this->orderDetailAccessFlags()
            )
        );
    }

    public function create(Order $order)
    {
        $this->authorizeOrderDetailCreate();

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

    public function gambar(OrderDetail $detail)
    {
        $this->authorizeOrderDetailAll();
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
                    mkdir($save_path, 0755, true);
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

        return redirect('/admin/order/' . $orderDetail->order->id . '/detail')->withSuccess(__('Gambar detail updated successfully.'));
    }

    public function updateStatus(Request $request, OrderDetail $detail)
    {
        abort_if($this->isMarketingOnly(), Response::HTTP_FORBIDDEN, '403 Forbidden');
        $this->authorizeOrderDetailLimited();

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

        return redirect('/admin/order/' . $detail->order->id . '/detail')->withSuccess(__('Status updated successfully.'));
    }

    public function advanceStatus(Request $request, OrderDetail $detail)
    {
        abort_if(Gate::denies('order_detail_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');
        abort_if(! $this->isProduksiLevel(), Response::HTTP_FORBIDDEN, '403 Forbidden');

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

                if (Produksi::shouldDeductStock($from, $to)) {
                    $stokService->kurang(
                        $detail->produk->id,
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

        $speks = Spek::all();

        return view(
            'admin.orderDetails.edit',
            array_merge(compact('detail', 'speks'), $this->orderDetailAccessFlags())
        );
    }

    public function update(Request $request, $detail)
    {
        $this->authorizeOrderDetailLimited();

        $orderDetail = OrderDetail::find($detail);

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
                    mkdir($save_path, 0755, true);
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

        $redirectUrl = route('order.detail', $orderDetail->order->id);
        $message = __('Gambar detail updated successfully.');

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['message' => $message]);
        }

        return redirect($redirectUrl)->withSuccess($message);
    }
}
