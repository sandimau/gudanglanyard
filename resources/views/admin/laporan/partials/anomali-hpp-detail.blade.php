@php $rupiah = fn($nilai) => 'Rp ' . number_format($nilai, 0, ',', '.'); @endphp
<h5 class="fw-semibold mb-1">{{ $produk->model }}{{ $produk->varian ? ' · ' . $produk->varian : '' }}</h5>
<p class="text-muted small">Produk #{{ $produk->id }} · satuan katalog: {{ $produk->satuan ?: 'tidak diatur' }} · HPP katalog saat ini: {{ $rupiah($produk->hpp_katalog) }}<br>
    Periode {{ $awal->format('d/m/Y') }} – {{ $akhir->format('d/m/Y') }}. Tabel dibatasi 100 transaksi terbaru; ringkasan produk menghitung seluruh transaksi.</p>
<div class="alert alert-warning small">Bandingkan satuan pembelian dengan satuan penjualan. HPP tercatat pada transaksi tidak otomatis berubah jika HPP katalog diperbarui. Tanda “katalog” berarti HPP transaksi kosong/nol dan perhitungan memakai HPP katalog saat ini.</div>
<div class="table-responsive"><table class="table table-hover align-middle">
    <thead><tr><th>Tanggal</th><th>Sumber</th><th class="text-end">Unit</th><th class="text-end">Harga/unit</th><th class="text-end">HPP/unit</th><th class="text-end">Nilai jual</th><th class="text-end">Total HPP</th><th></th></tr></thead>
    <tbody>
        @forelse ($transaksi as $row)
            <tr class="{{ $row->hpp_total > $row->jual_total ? 'table-warning' : '' }}">
                <td>{{ \Carbon\Carbon::parse($row->tanggal)->format('d/m/Y') }}</td><td>{{ $row->sumber === 'order' ? 'Order' : 'Marketplace' }}</td>
                <td class="text-end">{{ number_format($row->jumlah, 0, ',', '.') }}</td><td class="text-end text-nowrap">{{ $rupiah($row->harga_satuan) }}</td>
                <td class="text-end text-nowrap">{{ $rupiah($row->hpp_satuan) }}{{ $row->fallback ? ' (katalog)' : '' }}</td>
                <td class="text-end text-nowrap">{{ $rupiah($row->jual_total) }}</td><td class="text-end text-nowrap">{{ $rupiah($row->hpp_total) }}</td>
                <td><a href="{{ $row->sumber === 'order' ? route('order.detail', $row->transaksi_id) : route('projectmp.detail', $row->transaksi_id) }}" class="btn btn-sm btn-outline-primary">Buka</a></td>
            </tr>
        @empty
            <tr><td colspan="8" class="text-center text-muted">Tidak ada transaksi pada periode ini.</td></tr>
        @endforelse
    </tbody>
</table></div>
