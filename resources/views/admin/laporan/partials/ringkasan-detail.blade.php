<h5 class="fw-semibold mb-1">{{ $title }}</h5>
<p class="text-muted small mb-3">{{ $subtitle }} · menampilkan maksimal 100 baris terbaru. Nilai ringkasan tetap menghitung seluruh data.</p>
@if ($metric === 'margin')
    <p class="alert alert-info small">Omzet + HPP (ditampilkan sebagai pengurang) = selisih awal. Ini bukan laba bersih. Klik kartu omzet atau HPP untuk melihat transaksi pembentuknya.</p>
@endif
@if ($rows->isEmpty())
    <div class="text-center text-muted py-5">Tidak ada data untuk rincian ini.</div>
@else
    @php
        $adaTanggal = $rows->contains(fn($row) => !empty($row->tanggal));
        $adaJenis = $rows->contains(fn($row) => !empty($row->jenis) || ($scope === 'keuangan' && $metric === 'hpp'));
        $adaAksi = $scope === 'hutang' && $metric !== 'transaksi'
            || $scope === 'keuangan' && in_array($metric, ['piutang', 'hpp', 'offline', 'online-order', 'online-project', 'order-belum'], true);
    @endphp
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead><tr>@if ($adaTanggal)<th>Tanggal</th>@endif<th>Rincian</th>@if ($adaJenis)<th>Jenis</th>@endif<th class="text-end">Nilai</th>@if ($adaAksi)<th></th>@endif</tr></thead>
            <tbody>
                @foreach ($rows as $row)
                    <tr>
                        @if ($adaTanggal)<td>{{ !empty($row->tanggal) ? \Carbon\Carbon::parse($row->tanggal)->format('d/m/Y') : '—' }}</td>@endif
                        <td>
                            @if ($scope === 'keuangan' && $metric === 'hpp')
                                {{ $row->model }}{{ $row->varian ? ' · ' . $row->varian : '' }}
                                <small class="d-block text-muted">{{ number_format($row->jumlah, 0, ',', '.') }} unit × Rp {{ number_format($row->hpp_satuan, 0, ',', '.') }}/unit{{ $row->fallback ? ' (HPP katalog)' : '' }}</small>
                            @elseif ($scope === 'keuangan' && in_array($metric, ['omzet', 'offline', 'online-order', 'online-project', 'order-belum'], true))
                                {{ $row->nama }} #{{ $row->id }}@if (!empty($row->kontak) || !empty($row->konsumen)) · {{ $row->kontak ?? $row->konsumen }}@endif
                            @elseif ($scope === 'keuangan' && in_array($metric, ['operasional', 'kategori'], true))
                                {{ $row->model ?: ($row->nama ?: 'Item belanja #' . $row->id) }}{{ $row->model && $row->nama ? ' · ' . $row->nama : '' }}
                                <small class="d-block text-muted">{{ number_format($row->jumlah, 0, ',', '.') }} unit × Rp {{ number_format($row->harga, 0, ',', '.') }}</small>
                            @elseif ($scope === 'keuangan' && in_array($metric, ['gaji', 'tunjangan'], true))
                                {{ $row->nama }} #{{ $row->id }}
                                @if ($row->penerima) · {{ $row->penerima }} @endif
                                @if ($metric === 'tunjangan' && $row->ket)
                                    <small class="d-block text-muted">{{ $row->ket }}</small>
                                @endif
                            @elseif ($scope === 'keuangan' && $metric === 'piutang')
                                {{ $row->nama ?: 'Piutang #' . $row->id }}
                            @elseif ($scope === 'hutang')
                                {{ $row->nama ?: 'Tanpa kontak' }}
                            @else
                                {{ $row->nama ?: 'Rincian' }}
                            @endif
                        </td>
                        @if ($adaJenis)<td>{{ $scope === 'keuangan' && $metric === 'hpp' ? ($row->sumber === 'order' ? 'Order' : 'Marketplace') : ucfirst($row->jenis) }}</td>@endif
                        <td class="text-end fw-semibold text-nowrap">Rp {{ number_format($scope === 'keuangan' && $metric === 'hpp' ? $row->hpp_total : $row->nilai, 0, ',', '.') }}</td>
                        @if ($adaAksi)<td>
                            @if ($scope === 'hutang' && $metric !== 'transaksi')
                                <button type="button" class="btn btn-sm btn-outline-primary" data-ringkasan-url="{{ route('laporan.ringkasanDetail', ['scope' => 'hutang', 'metric' => 'transaksi', 'id' => $row->id]) }}">Riwayat</button>
                            @elseif ($scope === 'keuangan' && $metric === 'piutang')
                                <a href="{{ route('hutang.detail', $row->id) }}" class="btn btn-sm btn-outline-primary">Buka</a>
                            @elseif ($scope === 'keuangan' && $metric === 'hpp')
                                <a href="{{ $row->sumber === 'order' ? route('order.detail', $row->transaksi_id) : route('projectmp.detail', $row->transaksi_id) }}" class="btn btn-sm btn-outline-primary">Buka</a>
                            @elseif ($scope === 'keuangan' && in_array($metric, ['offline', 'online-order', 'order-belum'], true))
                                <a href="{{ route('order.detail', $row->id) }}" class="btn btn-sm btn-outline-primary">Buka</a>
                            @elseif ($scope === 'keuangan' && $metric === 'online-project')
                                <a href="{{ route('projectmp.detail', $row->id) }}" class="btn btn-sm btn-outline-primary">Buka</a>
                            @endif
                        </td>@endif
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif
@if ($metric === 'transaksi')
    <h6 class="mt-4">Pembayaran sampai hari ini</h6>
    @forelse ($history as $payment)
        <div class="d-flex justify-content-between border-bottom py-2 gap-3"><span>{{ \Carbon\Carbon::parse($payment->tanggal)->format('d/m/Y') }} · {{ $payment->keterangan }}</span><strong class="text-nowrap">Rp {{ number_format($payment->jumlah, 0, ',', '.') }}</strong></div>
    @empty
        <p class="text-muted">Belum ada pembayaran.</p>
    @endforelse
@endif
