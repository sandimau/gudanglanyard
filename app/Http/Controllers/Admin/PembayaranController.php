<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AkunDetail;
use App\Models\Order;
use App\Models\Pembayaran;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;

class PembayaranController extends Controller
{
    public function index(Request $request)
    {
        abort_if(
            Gate::denies('keuangan') && Gate::denies('akun_detail_access'),
            Response::HTTP_FORBIDDEN,
            '403 Forbidden'
        );

        $filters = $request->validate([
            'dari' => ['nullable', 'date'],
            'sampai' => [
                'nullable',
                'date',
                Rule::when($request->filled('dari'), ['after_or_equal:dari']),
            ],
            'akun_detail_id' => ['nullable', 'integer', 'exists:akun_details,id'],
            'cari' => ['nullable', 'string', 'max:100'],
        ]);

        $query = Pembayaran::query()
            ->with(['akunDetail', 'order.kontak'])
            ->whereHas('order')
            ->when($filters['dari'] ?? null, function ($query, $dari) {
                $query->whereDate('pembayarans.created_at', '>=', $dari);
            })
            ->when($filters['sampai'] ?? null, function ($query, $sampai) {
                $query->whereDate('pembayarans.created_at', '<=', $sampai);
            })
            ->when($filters['akun_detail_id'] ?? null, function ($query, $akunDetailId) {
                $query->where('akun_detail_id', $akunDetailId);
            })
            ->when($filters['cari'] ?? null, function ($query, $cari) {
                $query->where(function ($query) use ($cari) {
                    $query->where('ket', 'like', "%{$cari}%")
                        ->orWhereHas('order', function ($query) use ($cari) {
                            $query->where('nota', 'like', "%{$cari}%")
                                ->orWhereHas('kontak', function ($query) use ($cari) {
                                    $query->where('nama', 'like', "%{$cari}%");
                                });
                        });
                });
            });

        $totalPembayaran = (clone $query)->sum('jumlah');
        $pembayarans = $query
            ->latest('pembayarans.created_at')
            ->latest('pembayarans.id')
            ->paginate(20)
            ->withQueryString();

        $akunDetails = AkunDetail::query()
            ->whereHas('akun_kategori', function ($query) {
                $query->whereIn('id', [1, 8]);
            })
            ->orderBy('nama')
            ->pluck('nama', 'id');

        return view('admin.pembayarans.index', compact('pembayarans', 'akunDetails', 'totalPembayaran'));
    }

    public function show(Order $order)
    {
        abort_if(
            Gate::denies('order_detail_access')
                && Gate::denies('keuangan')
                && Gate::denies('akun_detail_access'),
            Response::HTTP_FORBIDDEN,
            '403 Forbidden'
        );

        $order->load('kontak');
        $pembayarans = $order->pembayaran()
            ->with('akunDetail')
            ->latest('created_at')
            ->latest('id')
            ->paginate(20);

        return view('admin.pembayarans.show', compact('order', 'pembayarans'));
    }
}
