@extends('layouts.app')

@section('title', 'Ringkasan Keuangan')

@push('after-styles')
    <style>
        .summary-shell { --summary-ink: #26344e; color: var(--summary-ink); }
        .summary-hero { background: linear-gradient(115deg, #172e55, #294d80 65%, #39728d); border-radius: 20px; color: #fff; padding: 26px 30px; }
        .summary-hero .text-muted { color: #d7e5f3 !important; }
        .summary-panel { border: 1px solid rgba(128, 145, 170, .22); border-radius: 18px; box-shadow: 0 8px 28px rgba(25, 47, 77, .06); }
        .summary-number { font-size: clamp(1.25rem, 2vw, 1.85rem); font-weight: 750; overflow-wrap: anywhere; }
        .summary-click { display: block; width: 100%; padding: 0; background: none; border: 0; color: inherit; text-align: left; cursor: pointer; }
        .summary-click:hover .summary-number, .summary-row:hover span { color: #337bbb; }
        .summary-click:focus-visible { outline: 3px solid #4da0e7; outline-offset: 4px; border-radius: 8px; }
        .summary-row { display: flex; justify-content: space-between; gap: 12px; padding: 12px 0; border-bottom: 1px solid rgba(128, 145, 170, .18); align-items: center; }
        .summary-row strong { white-space: nowrap; }
        .summary-bar { height: 9px; background: rgba(128,145,170,.17); border-radius: 12px; overflow: hidden; margin: 4px 0 8px; }
        .summary-bar > span { display: block; height: 100%; background: linear-gradient(90deg, #2b84b8, #58b6b5); border-radius: inherit; }
        .summary-hero a { color: #fff; }
        [data-theme='dark'] .summary-shell { --summary-ink: #e7edf7; }
    </style>
@endpush

@section('content')
    @php
        $rupiah = fn($angka) => 'Rp ' . number_format($angka, 0, ',', '.');
        $detail = fn($metric, $extra = []) => route('laporan.ringkasanDetail', array_merge(['scope' => 'keuangan', 'metric' => $metric], $extra));
        $labaKotor = $omzet - $hpp;
        $margin = $omzet > 0 ? $labaKotor / $omzet * 100 : 0;
    @endphp
    <div class="summary-shell">
        <div class="summary-hero d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
            <div>
                <h1 class="h3 fw-bold mb-1">Ringkasan Keuangan</h1>
                <p class="text-muted mb-0">Aktivitas {{ $awal->format('d/m/Y') }} – {{ $akhir->format('d/m/Y') }} · 30 hari · seluruh cabang</p>
            </div>
            <a href="{{ route('laporan.ringkasanHutang') }}" class="btn btn-outline-light">Ringkasan Hutang <span aria-hidden="true">↗</span></a>
        </div>

        <div class="row g-3 mb-4">
            @foreach ([
                ['omzet', 'Omzet transaksi', $omzet, 'Order + proyek marketplace', 'primary'],
                ['hpp', 'HPP produk terjual', $hpp, 'Biaya unit yang sudah terjual', 'warning'],
                ['margin', 'Selisih omzet − HPP', $labaKotor, 'Margin awal ' . number_format($margin, 1, ',', '.') . '% · bukan laba bersih', $labaKotor < 0 ? 'danger' : 'success'],
                ['kas', 'Saldo kas / bank', $kas, 'Posisi saat ini, bukan omzet', 'info'],
            ] as [$metric, $label, $value, $hint, $tone])
                <div class="col-sm-6 col-xl-3"><div class="card summary-panel h-100"><div class="card-body">
                    <button type="button" class="summary-click h-100" data-ringkasan-url="{{ $detail($metric) }}" aria-label="Lihat rincian {{ $label }}">
                        <span class="badge bg-{{ $tone }} bg-opacity-10 text-{{ $tone }} mb-3">{{ $label }}</span>
                        <span class="summary-number d-block mb-2">{{ $rupiah($value) }}</span>
                        <span class="small text-muted d-block">{{ $hint }}</span><span class="small text-primary d-block mt-2">Lihat rincian ↗</span>
                    </button>
                </div></div></div>
            @endforeach
        </div>

        <div class="card summary-panel mb-4"><div class="card-body p-4">
            <div class="d-flex flex-wrap justify-content-between gap-2 mb-3">
                <div><h2 class="h5 fw-bold mb-1">Periksa HPP produk</h2>
                    <p class="small text-muted mb-0">Produk dengan total HPP transaksi lebih besar daripada nilai jual item pada 30 hari ini. Urut selisih terbesar; klik produk untuk rincian transaksi.</p></div>
                @if ($anomaliHpp->isNotEmpty())<span class="badge bg-warning text-dark align-self-start">{{ $anomaliHpp->count() }} produk perlu ditinjau</span>@endif
            </div>
            @if ($anomaliHpp->isEmpty())
                <p class="text-muted mb-0">Tidak ada produk dengan total HPP di atas nilai jual item selama periode ini.</p>
            @else
                <div class="table-responsive"><table class="table table-hover align-middle mb-0">
                    <thead><tr><th>Produk</th><th class="text-end">Unit</th><th class="text-end">Nilai jual item</th><th class="text-end">HPP</th><th class="text-end">Selisih</th><th></th></tr></thead>
                    <tbody>
                        @foreach ($anomaliHpp->take(10) as $produk)
                            <tr><td><button type="button" class="summary-click fw-semibold" data-ringkasan-url="{{ $detail('hpp-anomali', ['id' => $produk->produk_id]) }}">{{ $produk->model }}{{ $produk->varian ? ' · ' . $produk->varian : '' }} <span class="text-primary">↗</span></button><small class="text-muted">ID {{ $produk->produk_id }}</small></td>
                                <td class="text-end">{{ number_format($produk->unit, 0, ',', '.') }}</td><td class="text-end text-nowrap">{{ $rupiah($produk->nilai_jual) }}</td><td class="text-end text-nowrap">{{ $rupiah($produk->nilai_hpp) }}</td><td class="text-end text-danger fw-semibold text-nowrap">{{ $rupiah($produk->nilai_hpp - $produk->nilai_jual) }}</td>
                                <td><button type="button" class="btn btn-sm btn-outline-primary" data-ringkasan-url="{{ $detail('hpp-anomali', ['id' => $produk->produk_id]) }}">Detail</button></td></tr>
                        @endforeach
                    </tbody>
                </table></div>
                @if ($anomaliHpp->count() > 10)<p class="small text-muted mt-3 mb-0">Menampilkan 10 selisih terbesar dari {{ $anomaliHpp->count() }} produk.</p>@endif
            @endif
            <p class="small text-muted mt-3 mb-0">Perbandingan ini menggunakan nilai item sebelum penyesuaian ongkir/diskon tingkat pesanan. Tidak semua selisih berarti data salah; cek satuan pembelian dan penjualan sebelum mengubah HPP. Data historis tidak diubah oleh halaman ini.</p>
        </div></div>

        <div class="row g-3 mb-4">
            <div class="col-lg-6"><div class="card summary-panel h-100"><div class="card-body p-4">
                <h2 class="h5 fw-bold mb-1">Sumber penjualan</h2><p class="text-muted small mb-3">Klik setiap sumber untuk melihat transaksinya.</p>
                @foreach ([['offline', 'Order non-marketplace', $offline], ['online-order', 'Order marketplace', $onlineOrder], ['online-project', 'Proyek marketplace', $onlineProject]] as [$key, $label, $value])
                    <button type="button" class="summary-click summary-row" data-ringkasan-url="{{ $detail($key) }}"><span>{{ $label }} ↗</span><strong>{{ $rupiah($value) }}</strong></button>
                    <div class="summary-bar"><span style="width: {{ $omzet > 0 ? min(100, max(0, $value / $omzet * 100)) : 0 }}%"></span></div>
                @endforeach
                <button type="button" class="summary-click summary-row fw-bold" data-ringkasan-url="{{ $detail('omzet') }}"><span>Total omzet ↗</span><strong>{{ $rupiah($omzet) }}</strong></button>
            </div></div></div>
            <div class="col-lg-6"><div class="card summary-panel h-100"><div class="card-body p-4">
                <h2 class="h5 fw-bold mb-1">Beban yang tercatat</h2><p class="text-muted small mb-3">Di luar HPP, potongan marketplace, dan kerugian lain.</p>
                @foreach ([['operasional', 'Belanja operasional non-stok', $operasional], ['gaji', 'Penggajian termasuk kasbon', $gaji], ['tunjangan', 'Tunjangan', $tunjangan]] as [$key, $label, $value])
                    <button type="button" class="summary-click summary-row" data-ringkasan-url="{{ $detail($key) }}"><span>{{ $label }} ↗</span><strong>{{ $rupiah($value) }}</strong></button>
                @endforeach
                <button type="button" class="summary-click summary-row fw-bold" data-ringkasan-url="{{ $detail('beban') }}"><span>Total tiga kelompok ↗</span><strong>{{ $rupiah($operasional + $gaji + $tunjangan) }}</strong></button>
                <a href="{{ route('laporan.labarugi') }}" class="small d-inline-block mt-3">Laporan laba rugi lengkap ↗</a>
            </div></div></div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-lg-6"><div class="card summary-panel h-100"><div class="card-body p-4">
                <h2 class="h5 fw-bold mb-1">Belanja operasional per kategori</h2><p class="text-muted small">Klik kategori untuk melihat item pembelian.</p>
                @forelse ($biayaKategori as $kategori)
                    <button type="button" class="summary-click summary-row" data-ringkasan-url="{{ $detail('kategori', ['kategori' => $kategori->kategori]) }}"><span>{{ $kategori->kategori }} ↗</span><strong>{{ $rupiah($kategori->total) }}</strong></button>
                @empty
                    <p class="text-muted mb-0">Tidak ada belanja non-stok pada periode ini.</p>
                @endforelse
            </div></div></div>
            <div class="col-lg-6"><div class="card summary-panel h-100"><div class="card-body p-4">
                <h2 class="h5 fw-bold mb-1">Tagihan yang belum diterima</h2><p class="text-muted small">Posisi sampai hari ini, termasuk transaksi lama.</p>
                <button type="button" class="summary-click summary-row" data-ringkasan-url="{{ $detail('piutang') }}"><span>Piutang tercatat ↗</span><strong>{{ $rupiah($piutang) }}</strong></button>
                <button type="button" class="summary-click summary-row" data-ringkasan-url="{{ $detail('order-belum') }}"><span>Order non-marketplace belum lunas ↗</span><strong>{{ $rupiah($belumDibayarOrder) }}</strong></button>
                <p class="small text-muted mt-3 mb-0">Tidak dijumlahkan: keduanya bisa mencatat tagihan yang sama dan belum menjadi uang tunai.</p>
            </div></div></div>
        </div>

        <div class="alert alert-info">Kas adalah saldo saat ini; omzet dan biaya menggunakan 30 hari terakhir. Selisih omzet − HPP bukan laba bersih atau arus kas. Lihat juga <a href="{{ route('laporan.ringkasanHutang') }}">saldo hutang</a> dan <a href="{{ route('laporan.neraca') }}">neraca</a>.</div>
    </div>
    @include('admin.laporan.partials.ringkasan-popup')
@endsection
