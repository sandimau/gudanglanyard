<?php

namespace App\Http\Controllers\Admin;

use Gate;
use App\Models\Chat;
use App\Models\Spek;
use App\Models\Order;
use App\Models\Kontak;
use App\Models\Member;
use App\Models\Gaji;
use App\Models\Produk;
use App\Models\Sistem;
use App\Models\Produksi;
use App\Models\BukuBesar;
use App\Models\ProjectMp;
use App\Models\AkunDetail;
use App\Models\Pembayaran;
use App\Models\OrderDetail;
use Illuminate\Http\Request;
use App\Models\ProdukProduksi;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Symfony\Component\HttpFoundation\Response;

class OrderController extends Controller
{
    private function hasRoleInsensitive(string ...$names): bool
    {
        $normalized = collect($names)->map(fn ($name) => strtolower($name));

        return auth()->user()->roles->contains(
            fn ($role) => $normalized->contains(strtolower($role->name))
        );
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

    public function apiKonsumen()
    {
        if (isset($_GET['id'])) {
            // Get single konsumen by ID
            $kontak = Kontak::select('nama', 'id', 'perusahaan')->where('id', $_GET['id'])->first();
            return response()->json($kontak);
        } else {
            // Search konsumen by query
            $kontak = Kontak::select('nama', 'id', 'perusahaan')->where('konsumen', 1)->where('nama', 'LIKE', '%' . $_GET['q'] . '%')->get();
            return response()->json($kontak);
        }
    }

    public function apiKontak()
    {
        $kontak = Kontak::select('nama', 'id', 'perusahaan')->where('nama', 'LIKE', '%' . $_GET['q'] . '%')->get();
        return response()->json($kontak);
    }

    public function apiSupplier()
    {
        $kontak = Kontak::select('nama', 'id')->where('supplier', 1)->where('nama', 'LIKE', '%' . $_GET['q'] . '%')->get();
        return response()->json($kontak);
    }

    public function apiProduk()
    {
        if (isset($_GET['id'])) {
            // Get single produk by ID
            $produk = Produk::select('produk_models.nama', 'produk_models.harga', 'produks.nama as varian', 'produks.id', 'produk_kategoris.nama as kategori')
                ->join('produk_models', 'produks.produk_model_id', '=', 'produk_models.id')
                ->join('produk_kategoris', 'produk_models.kategori_id', '=', 'produk_kategoris.id')
                ->where('produks.id', $_GET['id'])
                ->first();
            return response()->json($produk);
        } else {
            // Search produk by query
            $produk = Produk::select('produk_models.nama', 'produk_models.harga', 'produks.nama as varian', 'produks.id', 'produk_kategoris.nama as kategori')
                ->join('produk_models', 'produks.produk_model_id', '=', 'produk_models.id')
                ->join('produk_kategoris', 'produk_models.kategori_id', '=', 'produk_kategoris.id')
                ->where('produk_models.jual', 1)
                ->where('produks.status', 1)
                ->where(function ($query) {
                    $query->where('produks.nama', 'LIKE', '%' . $_GET['q'] . '%')
                        ->orWhere('produk_models.nama', 'LIKE', '%' . $_GET['q'] . '%')
                        ->orWhere('produk_kategoris.nama', 'LIKE', '%' . $_GET['q'] . '%');
                })
                ->get();
            return response()->json($produk);
        }
    }

    public function apiProdukBeli()
    {
        $produk = Produk::select(
            'produk_models.nama',
            'produk_models.satuan',
            'produks.nama as varian',
            'produks.id',
            'produk_kategoris.nama as kategori',
            DB::raw('COALESCE((SELECT harga FROM belanja_details WHERE produk_id = produks.id ORDER BY created_at DESC LIMIT 1), produk_models.harga) as harga')
        )
            ->join('produk_models', 'produks.produk_model_id', '=', 'produk_models.id')
            ->join('produk_kategoris', 'produk_models.kategori_id', '=', 'produk_kategoris.id')
            ->where('produk_models.beli', 1)
            ->where('produks.status', 1)
            ->where(function ($query) {
                $query->where('produks.nama', 'LIKE', '%' . $_GET['q'] . '%')
                    ->orWhere('produk_models.nama', 'LIKE', '%' . $_GET['q'] . '%')
                    ->orWhere('produk_kategoris.nama', 'LIKE', '%' . $_GET['q'] . '%');
            })
            ->get();
        return response()->json($produk);
    }

    public function apiProdukProduksi()
    {
        $produk = ProdukProduksi::select(
            'produk_models.nama',
            'produk_models.satuan',
            'produks.nama as varian',
            'produks.id',
            'produk_kategoris.nama as kategori',
            'perbandingan',
            DB::raw('COALESCE((SELECT harga FROM belanja_details WHERE produk_id = produks.id ORDER BY created_at DESC LIMIT 1), produk_models.harga) as harga')
        )
            ->join('produks', 'produk_produksis.produk_id', '=', 'produks.id')
            ->join('produk_models', 'produks.produk_model_id', '=', 'produk_models.id')
            ->join('produk_kategoris', 'produk_models.kategori_id', '=', 'produk_kategoris.id')
            ->where('produk_models.produksi', 1)
            ->where('produks.status', 1)
            ->where(function ($query) {
                $q = $_GET['q'] ?? '';
                $query->where('produks.nama', 'LIKE', '%' . $q . '%')
                    ->orWhere('produk_models.nama', 'LIKE', '%' . $q . '%')
                    ->orWhere('produk_kategoris.nama', 'LIKE', '%' . $q . '%');
            })
            ->get();
        return response()->json($produk);
    }

    public function apiProdukStok()
    {
        $produk = Produk::select(
            'produk_models.nama',
            'produk_models.satuan',
            'produks.nama as varian',
            'produks.id',
            'produk_kategoris.nama as kategori',
            DB::raw('COALESCE((SELECT harga FROM belanja_details WHERE produk_id = produks.id ORDER BY created_at DESC LIMIT 1), produk_models.harga) as harga')
        )
            ->join('produk_models', 'produks.produk_model_id', '=', 'produk_models.id')
            ->join('produk_kategoris', 'produk_models.kategori_id', '=', 'produk_kategoris.id')
            ->where('produk_models.stok', 1)
            ->where('produks.status', 1)
            ->where(function ($query) {
                $query->where('produks.nama', 'LIKE', '%' . $_GET['q'] . '%')
                    ->orWhere('produk_models.nama', 'LIKE', '%' . $_GET['q'] . '%')
                    ->orWhere('produk_kategoris.nama', 'LIKE', '%' . $_GET['q'] . '%');
            })
            ->get();
        return response()->json($produk);
    }

    public function apiProduksi()
    {
        $produk = Produk::select(
            'produk_models.nama',
            'produk_models.satuan',
            'produks.nama as varian',
            'produks.id',
            'produk_kategoris.nama as kategori',
            DB::raw('COALESCE((SELECT harga FROM belanja_details WHERE produk_id = produks.id ORDER BY created_at DESC LIMIT 1), produk_models.harga) as harga')
        )
            ->join('produk_models', 'produks.produk_model_id', '=', 'produk_models.id')
            ->join('produk_kategoris', 'produk_models.kategori_id', '=', 'produk_kategoris.id')
            ->where('produk_models.produksi', 1)
            ->where('produks.status', 1)
            ->where(function ($query) {
                $query->where('produks.nama', 'LIKE', '%' . $_GET['q'] . '%')
                    ->orWhere('produk_models.nama', 'LIKE', '%' . $_GET['q'] . '%')
                    ->orWhere('produk_kategoris.nama', 'LIKE', '%' . $_GET['q'] . '%');
            })
            ->get();
        return response()->json($produk);
    }

    public function index(Request $request)
    {
        if ($request->dari == null && $request->sampai == null && $request->nota == null && $request->kontak_id == null && $request->produk_id == null) {
            $orders = Order::whereNull('marketplace')->orderBy('id', 'desc')->paginate(10);
        } else {
            $orders = Order::query()
                ->leftJoin('order_details', 'orders.id', '=', 'order_details.order_id')
                ->leftJoin('kontaks', 'orders.kontak_id', '=', 'kontaks.id')
                ->when($request->dari && $request->sampai, function ($query) use ($request) {
                    $query->whereBetween('orders.created_at', [$request->dari, $request->sampai]);
                })
                ->when($request->nota, function ($query) use ($request) {
                    $query->where('orders.nota', 'LIKE', '%' . $request->nota . '%');
                })
                ->when($request->kontak_id, function ($query) use ($request) {
                    $query->where('orders.kontak_id', $request->kontak_id);
                })
                ->when($request->produk_id, function ($query) use ($request) {
                    $query->where('order_details.produk_id', $request->produk_id);
                })
                ->select('orders.*')
                ->whereNull('kontaks.marketplace')
                ->distinct()
                ->orderBy('orders.id', 'desc')
                ->paginate(10)
                ->appends(['dari' => $request->dari, 'sampai' => $request->sampai, 'nota' => $request->nota, 'kontak_id' => $request->kontak_id, 'produk_id' => $request->produk_id]);
        }
        return view('admin.orders.index', compact('orders'));
    }

    public function marketplace(Request $request)
    {
        // Tentukan sorting berdasarkan parameter
        $sortBy = 'created_at';
        $sortDirection = 'desc';

        if ($request->sort) {
            switch ($request->sort) {
                case 'total_asc':
                    $sortBy = 'total';
                    $sortDirection = 'asc';
                    break;
                case 'total_desc':
                    $sortBy = 'total';
                    $sortDirection = 'desc';
                    break;
                case 'bersih_asc':
                    $sortBy = 'bayar';
                    $sortDirection = 'asc';
                    break;
                case 'bersih_desc':
                    $sortBy = 'bayar';
                    $sortDirection = 'desc';
                    break;
                case 'persentase_asc':
                    // Untuk persentase, kita perlu menghitung (total - bayar) / total * 100
                    // Kita akan handle ini dengan raw query
                    break;
                case 'persentase_desc':
                    // Untuk persentase, kita perlu menghitung (total - bayar) / total * 100
                    // Kita akan handle ini dengan raw query
                    break;
            }
        }

        if ($request->dari == null && $request->sampai == null && $request->nota == null && $request->kontak_id == null && $request->produk_id == null && $request->pembayaran == null) {
            // Jika hanya ada sorting tanpa filter lain
            if ($request->sort) {
                $query = Order::whereNotNull('marketplace')->orderBy('created_at', 'desc');

                if ($request->sort == 'persentase_asc' || $request->sort == 'persentase_desc') {
                    $direction = $request->sort == 'persentase_asc' ? 'asc' : 'desc';
                    $query->orderByRaw("CASE WHEN total = 0 THEN 0 ELSE ((total - bayar) / total * 100) END {$direction}");
                } else {
                    $query->orderBy($sortBy, $sortDirection);
                }

                $orders = $query->paginate(10)->appends(['sort' => $request->sort]);
            } else {
                $orders = Order::whereNotNull('marketplace')->orderBy('created_at', 'desc')->paginate(10);
            }
        } else {
            // Gunakan subquery untuk menghindari masalah pagination dengan JOIN dan DISTINCT
            $orderIds = Order::query()
                ->leftJoin('order_details', 'orders.id', '=', 'order_details.order_id')
                ->leftJoin('kontaks', 'orders.kontak_id', '=', 'kontaks.id')
                ->when($request->dari && $request->sampai, function ($query) use ($request) {
                    $query->whereBetween('orders.created_at', [$request->dari, $request->sampai]);
                })
                ->when($request->nota, function ($query) use ($request) {
                    $query->where('orders.nota', 'LIKE', '%' . $request->nota . '%');
                })
                ->when($request->kontak_id, function ($query) use ($request) {
                    $query->where('orders.kontak_id', $request->kontak_id);
                })
                ->when($request->produk_id, function ($query) use ($request) {
                    $query->where('order_details.produk_id', $request->produk_id);
                })
                ->when($request->pembayaran == '1', function ($query) use ($request) {
                    // Jika pembayaran = 1 (sudah dibayar), filter yang bayar >= total
                    $query->whereRaw('orders.bayar > 0');
                })
                ->when($request->pembayaran == '0', function ($query) use ($request) {
                    // Jika pembayaran = 0 (belum dibayar), filter yang bayar < total
                    $query->whereRaw('orders.bayar = 0 and orders.total > 0');
                })
                ->where('kontaks.marketplace', 1)
                ->distinct()
                ->pluck('orders.id');

            // Query utama untuk pagination
            $query = Order::whereIn('id', $orderIds);

            // Handle sorting untuk persentase
            if ($request->sort == 'persentase_asc' || $request->sort == 'persentase_desc') {
                $direction = $request->sort == 'persentase_asc' ? 'asc' : 'desc';
                $query->orderByRaw("CASE WHEN total = 0 THEN 0 ELSE ((total - bayar) / total * 100) END {$direction}");
            } else {
                $query->orderBy($sortBy, $sortDirection);
            }

            $orders = $query->paginate(10)
                ->appends([
                    'dari' => $request->dari,
                    'sampai' => $request->sampai,
                    'nota' => $request->nota,
                    'kontak_id' => $request->kontak_id,
                    'produk_id' => $request->produk_id,
                    'pembayaran' => $request->pembayaran,
                    'sort' => $request->sort
                ]);
        }

        $kontaks = Kontak::where('marketplace', 1)->get();
        return view('admin.orders.marketplace', compact('orders', 'kontaks'));
    }

    public function create()
    {
        $speks = Spek::all();
        return view('admin.orders.create', compact('speks'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'kontak_id' => 'required',
            'produk_id' => 'required',
            'harga' => 'required',
            'jumlah' => 'required',
            'deathline' => 'required',
        ]);

        $request->ongkir ? $ongkir = $request->ongkir : $ongkir = 0;

        $order['kontak_id'] = $request->kontak_id;
        $order['total'] = $request->jumlah * $request->harga;
        $order['jasa'] = $request->jasa;
        $order['keterangan'] = $request->keterangan;
        $order['ongkir'] = $ongkir;
        $order['pengiriman'] = $request->pengiriman;
        $order['invoice'] = $request->invoice;
        $order['jenis_pembayaran'] = $request->jenis_pembayaran;
        $order['ket_kirim'] = $request->ket_kirim;
        $order['deathline'] = $request->deathline;
        $order['nota'] = $request->nota ?: rand(1000000, 100);

        // ambil order flow setiap perusahaan
        $produksi = Produksi::initialStatus();

        $dataOrder = Order::create($order);

        //insert order detail
        $dataDetail['order_id'] = $dataOrder->id;
        $dataDetail['produk_id'] = $request->produk_id;
        $dataDetail['tema'] = $request->tema;
        $dataDetail['jumlah'] = $request->jumlah;
        $dataDetail['harga'] = $request->harga;
        $dataDetail['keterangan'] = $request->keterangan;
        $dataDetail['produksi_id'] = $produksi?->id;
        $dataDetail['deathline'] = $request->deathline;
        $dataDetail['nota'] = $dataOrder->nota;

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
        return redirect('/admin/order/' . $dataOrder->id . '/detail')->withSuccess(__('Order created successfully.'));
    }

    public function dashboard()
    {
        $produksis = Produksi::groupedForDashboard();

        $isProduksiLevel = $this->isProduksiLevel();
        $dashboardData = $this->loadDashboardData();

        return view('admin.orders.dashboard', array_merge(
            compact('produksis', 'isProduksiLevel'),
            $dashboardData
        ));
    }

    private function loadDashboardData(): array
    {
        $details = OrderDetail::query()
            ->forDashboard()
            ->whereNotNull('order_id')
            ->with([
                'order.kontak.ar',
                'produk.produkModel.kategori.kategoriUtama',
                'pemproses',
                'produksi',
            ])
            ->orderBy('order_id')
            ->get();

        $detailsByProduksiId = $details->groupBy('produksi_id');
        $orderCountsByProduksiId = $details->groupBy('produksi_id')->map(
            fn ($group) => $group->pluck('order_id')->unique()->count()
        );

        return compact('detailsByProduksiId', 'orderCountsByProduksiId');
    }

    public function edit(Order $order)
    {
        $speks = Spek::all();
        return view('admin.orders.edit', compact('order', 'speks'));
    }

    public function update(Request $request, Order $order)
    {
        $order->update($request->all());

        return redirect('admin/order/' . $order->id . '/detail')->withSuccess(__('Order updated successfully.'));
    }

    public function invoice($order)
    {
        $order = Order::where('id', $order)->with(['orderDetail'])->first();
        $sistems = Sistem::get()->pluck('isi', 'nama');
        $member = Member::where('user_id', auth()->user()->id)->first();
        return view('admin.orders.invoice', compact('order', 'sistems', 'member'));
    }

    public function unpaid(Request $request)
    {
        if ($request->dari == null && $request->sampai == null && $request->nota == null && $request->kontak_id == null) {
            $orders = Order::belumLunas()->orderBy('id', 'desc')->paginate(10);
        } else {
            $orders = Order::query()
                ->when($request->dari && $request->sampai, function ($query) use ($request) {
                    $query->whereBetween('created_at', [$request->dari, $request->sampai]);
                })
                ->when($request->nota, function ($query) use ($request) {
                    $query->where('nota', 'LIKE', '%' . $request->nota . '%');
                })
                ->when($request->kontak_id, function ($query) use ($request) {
                    $query->where('kontak_id', $request->kontak_id);
                })
                ->whereRaw('total > bayar')
                ->orderBy('id', 'desc')
                ->paginate(10)
                ->appends(['dari' => $request->dari, 'sampai' => $request->sampai, 'nota' => $request->nota, 'kontak_id' => $request->kontak_id]);
        }
        return view('admin.orders.unpaid', compact('orders'));
    }

    public function bayar(Order $order)
    {
        $kas = AkunDetail::where('akun_kategori_id', 1)->pluck('nama', 'id');
        return view('admin.orders.bayar', compact('order', 'kas'));
    }

    public function storeBayar(Request $request)
    {
        $order = Order::where('id', $request->order_id)->firstOrFail();
        $diskonBaru = (float) ($request->diskon ?? 0);
        $maxJumlah = max(0, $order->kekurangan - $diskonBaru);

        $request->validate([
            'jumlah' => 'required|numeric|min:1|max:' . $maxJumlah,
            'akun_detail_id' => 'required',
            'tanggal' => 'required',
            'diskon' => 'nullable|numeric|min:0|max:' . $order->kekurangan,
        ], [
            'jumlah.max' => 'Jumlah pembayaran tidak boleh melebihi total order (maksimal ' . number_format($maxJumlah, 0, ',', '.') . ').',
            'diskon.max' => 'Diskon tidak boleh melebihi kekurangan pembayaran.',
        ]);

        date_default_timezone_set("Asia/Jakarta");
        $time = date("h:i:s");
        $tanggal = request()->tanggal . ' ' . $time;

        DB::transaction(function () use ($tanggal) {
            //insert pembayarans table
            pembayaran::create([
                'akun_detail_id' => request()->akun_detail_id,
                'order_id' => request()->order_id,
                'jumlah' => request()->jumlah,
                'status' => 'approve',
                'ket' => request()->ket,
                'created_at' => $tanggal,
            ]);

            //update bayar order table
            $order = Order::where('id', request()->order_id)->first();
            $updatePembayaran = $order->bayar + request()->jumlah;
            $updateDiskon = $order->diskon + request()->diskon;
            $order->update([
                'bayar' => $updatePembayaran,
                'diskon' => $updateDiskon,
            ]);

            //insert buku besar table
            bukuBesar::create([
                'akun_detail_id' => request()->akun_detail_id,
                'ket' => 'pembayaran dari ' . $order->kontak->nama,
                'kredit' => 0,
                'kode' => 'byr',
                'debet' => request()->jumlah,
                'detail_id' => $order->id,
            ]);
        });

        return redirect('admin/order/belumLunas')->withSuccess(__('Pembayaran created successfully.'));
    }

    public function storeChat(Request $request, Order $order)
    {
        $member = Member::where('user_id', auth()->user()->id)->first();

        Chat::create([
            'isi' => $request->isi,
            'member_id' => $member?->id,
            'user_id' => $member ? null : auth()->id(),
            'order_id' => $order->id,
        ]);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['message' => __('chat created successfully.')]);
        }

        return redirect('admin/order/' . $order->id . '/detail')->withSuccess(__('chat created successfully.'));
    }

    public function omzet()
    {
        abort_if(Gate::denies('omzet_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');
        $orders = Order::omzetTahun()->get()->keyBy('year');
        $projects = ProjectMp::omzetTahun()->get()->keyBy('year');

        // Gabungkan semua tahun unik dari Order dan ProjectMp, urutkan kronologis
        $allYears = $orders->keys()->merge($projects->keys())->unique()->sort()->values();

        $chartData = $allYears->map(function ($year) use ($orders, $projects) {
            $order = $orders->get($year);
            $project = $projects->get($year);
            return (object) [
                'year' => $year,
                'omzet' => (float) ($order?->sum ?? 0),
                'omzetMp' => (float) ($project?->sumMp ?? 0),
            ];
        });

        return view('admin.orders.omzet', compact('chartData'));
    }

    public function omzetBulan()
    {
        abort_if(Gate::denies('omzet_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');
        $orders = Order::omzetBulan()->get()->keyBy('month');
        $projects = ProjectMp::omzetBulan()->get()->keyBy('month');

        // Gabungkan semua bulan unik dari Order dan ProjectMp, urutkan kronologis
        $allMonths = $orders->keys()->merge($projects->keys())->unique()->sort()->values();

        $chartData = $allMonths->map(function ($month) use ($orders, $projects) {
            $order = $orders->get($month);
            $project = $projects->get($month);
            return (object) [
                'month' => $month,
                'year' => $order?->year ?? $project?->year,
                'monthname' => $order?->monthname ?? $project?->monthname,
                'omzet' => (float) ($order?->omzet ?? 0),
                'omzetMp' => (float) ($project?->omzetMp ?? 0),
            ];
        });

        return view('admin.orders.omzetBulan', compact('chartData'));
    }

    public function hapusCancel()
    {
        Order::where('total', 0)
            ->where('marketplace', 1)
            ->where('created_at', '<=', now()->subDay())
            ->delete();
        return redirect()->back()->withSuccess(__('Order canceled telah dihapus.'));
    }

    public function hapusOnline()
    {
        Order::where('total', 0)
            ->where('marketplace', 1)
            ->where('created_at', '<=', now()->subDay())
            ->delete();
    }
}
