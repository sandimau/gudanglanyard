@extends('layouts.app')

@section('title', 'Laba Rugi Bahan')

@section('content')
    @php
        $totalKonsumen = $data->sum(fn ($item) => ($item->konsumen->omzet ?? 0) - ($item->konsumen->biaya ?? 0));
        $totalProduksi = $data->sum(fn ($item) => $item->produksi && !$item->produksi->tanpa_harga
            ? $item->produksi->nilai - $item->produksi->biaya : 0);
        $tanpaHarga = $data->sum(fn ($item) => $item->produksi->tanpa_harga ?? 0);
    @endphp
    <div class="card">
        <div class="card-header">
            <form action="{{ route('laporan.bahan') }}" method="get" class="d-flex align-items-center gap-2">
                <label for="bulan" class="mb-0">Bulan</label>
                <input type="month" id="bulan" name="bulan" value="{{ $bulan }}" class="form-control" style="max-width: 200px" required>
                <button type="submit" class="btn btn-primary">Tampilkan</button>
            </form>
        </div>
        <div class="card-body">
            <h4>Laba Rugi Bahan — {{ \Carbon\Carbon::createFromFormat('!Y-m', $bulan)->translatedFormat('F Y') }}</h4>
            <p class="text-muted">Konsumen: nilai penjualan dari detail order dan marketplace dikurangi HPP (belum termasuk ongkir/diskon).
                Pemakaian bahan: <strong>estimasi, bukan penjualan nyata</strong>; hanya produk kategori bahan dari menu Inventory → Pemakaian. HPP dan harga katalog disimpan saat pencatatan; catatan lama tanpa harga tersimpan menggunakan harga katalog saat ini.</p>
            @if ($tanpaHarga > 0)
                <div class="alert alert-warning">Ada {{ number_format($tanpaHarga, 0, ',', '.') }} unit bahan produksi tanpa harga katalog. Estimasi produksi untuk produk tersebut tidak ditampilkan dan tidak termasuk total.</div>
            @endif
            <div class="table-responsive">
                <table class="table table-striped table-bordered">
                    <thead>
                        <tr>
                            <th rowspan="2">Kategori</th>
                            <th rowspan="2">Produk</th>
                            <th colspan="4" class="text-center">Dijual ke Konsumen</th>
                            <th colspan="4" class="text-center">Pemakaian Bahan (Estimasi)</th>
                        </tr>
                        <tr>
                            <th>Jumlah</th><th>Penjualan</th><th>HPP</th><th>Untung / Rugi</th>
                            <th>Jumlah</th><th>Nilai Katalog</th><th>HPP</th><th>Untung / Rugi*</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($data as $item)
                            @php
                                $jual = $item->konsumen;
                                $pakai = $item->produksi;
                                $labaJual = ($jual->omzet ?? 0) - ($jual->biaya ?? 0);
                                $labaPakai = ($pakai->nilai ?? 0) - ($pakai->biaya ?? 0);
                                $estimasiAda = $pakai && !$pakai->tanpa_harga;
                            @endphp
                            <tr>
                                <td>{{ $item->kategori_utama }} / {{ $item->kategori }}</td>
                                <td>{{ $item->model }}{{ $item->varian ? ' (' . $item->varian . ')' : '' }}</td>
                                <td>{{ number_format($jual->jumlah ?? 0, 0, ',', '.') }}</td>
                                <td>{{ number_format($jual->omzet ?? 0, 0, ',', '.') }}</td>
                                <td>{{ number_format($jual->biaya ?? 0, 0, ',', '.') }}</td>
                                <td class="{{ $labaJual < 0 ? 'text-danger' : 'text-success' }}">{{ number_format($labaJual, 0, ',', '.') }}</td>
                                <td>{{ number_format($pakai->jumlah ?? 0, 0, ',', '.') }}</td>
                                <td>{{ $pakai && !$estimasiAda ? '—' : number_format($pakai->nilai ?? 0, 0, ',', '.') }}</td>
                                <td>{{ number_format($pakai->biaya ?? 0, 0, ',', '.') }}</td>
                                <td class="{{ $labaPakai < 0 && $estimasiAda ? 'text-danger' : 'text-success' }}">{{ $pakai && !$estimasiAda ? '—' : number_format($labaPakai, 0, ',', '.') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="10" class="text-center">Tidak ada transaksi bahan pada bulan ini.</td></tr>
                        @endforelse
                    </tbody>
                    <tfoot>
                        <tr class="table-primary font-weight-bold">
                            <td colspan="5">Total Untung / Rugi</td>
                            <td class="{{ $totalKonsumen < 0 ? 'text-danger' : 'text-success' }}">{{ number_format($totalKonsumen, 0, ',', '.') }}</td>
                            <td colspan="3">Total estimasi produksi</td>
                            <td class="{{ $totalProduksi < 0 ? 'text-danger' : 'text-success' }}">{{ number_format($totalProduksi, 0, ',', '.') }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            <small class="text-muted">* Pemakaian bahan mengurangi stok. Angka estimasi hanya perkiraan selisih nilai, bukan keuntungan usaha yang terealisasi.</small>
        </div>
    </div>
@endsection
