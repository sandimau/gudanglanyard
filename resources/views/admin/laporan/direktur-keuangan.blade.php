@extends('layouts.app')

@section('title', 'Ringkasan Hutang')

@push('after-styles')
    <style>
        .direktur-card { border: 1px solid rgba(128, 145, 170, .22); border-radius: 18px; box-shadow: 0 8px 28px rgba(25, 47, 77, .06); }
        .direktur-number { font-size: clamp(1.25rem, 2vw, 1.85rem); font-weight: 700; overflow-wrap: anywhere; }
        .direktur-label { font-size: .8rem; letter-spacing: .02em; }
        .direktur-hero { background: linear-gradient(115deg, #172e55, #294d80 65%, #39728d); border-radius: 20px; color: #fff; padding: 26px 30px; }
        .direktur-hero .text-muted { color: #d7e5f3 !important; }
        .direktur-click { border: 0; padding: 0; width: 100%; background: none; color: inherit; text-align: left; cursor: pointer; }
        .direktur-click:hover .direktur-number, .direktur-click:hover span { color: #3589c8; }
        .direktur-click:focus-visible { outline: 3px solid #4da0e7; outline-offset: 4px; border-radius: 8px; }
    </style>
@endpush

@section('content')
    @php
        $rupiah = fn($nilai) => 'Rp ' . number_format($nilai, 0, ',', '.');
        $detail = fn($metric, $extra = []) => route('laporan.ringkasanDetail', array_merge(['scope' => 'hutang', 'metric' => $metric], $extra));
        $perubahan = $saldoAkhir - $saldoAwal;
    @endphp
    <div class="direktur-hero d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <h2 class="h4 fw-bold mb-1">Ringkasan Hutang</h2>
            <p class="text-muted mb-0">30 hari kalender, {{ $awal->format('d/m/Y') }} – {{ $akhir->format('d/m/Y') }} · seluruh cabang</p>
        </div>
        <div class="d-flex gap-2"><a href="{{ route('laporan.ringkasanKeuangan') }}" class="btn btn-outline-light btn-sm">Ringkasan Keuangan ↗</a><a href="{{ route('hutang.index', ['status' => 'belum_lunas']) }}" class="btn btn-light btn-sm">Semua hutang ↗</a></div>
    </div>

    <div class="alert {{ $perubahan > 0 ? 'alert-warning' : 'alert-info' }} mb-4" role="status">
        @if ($perubahan > 0)
            <strong>Hutang naik {{ $rupiah($perubahan) }}.</strong> Hutang baru melampaui pembayaran selama 30 hari ini.
        @elseif ($perubahan < 0)
            <strong>Hutang turun {{ $rupiah(abs($perubahan)) }}.</strong> Pembayaran melampaui hutang baru selama 30 hari ini.
        @else
            <strong>Saldo hutang tidak berubah.</strong> Hutang baru dan pembayaran bernilai sama selama 30 hari ini.
        @endif
        @if ($tunggakanLama > 0)
            <span class="d-block mt-1">Masih ada {{ $rupiah($tunggakanLama) }} dari {{ $jumlahTunggakanLama }} hutang yang dibuat sebelum periode ini dan belum lunas.</span>
        @endif
    </div>

    <div class="row g-3 mb-4">
        @foreach ([['awal', 'Saldo hutang awal periode', $saldoAwal, 'Sisa per ' . $awal->copy()->subDay()->format('d/m/Y'), ''], ['baru', 'Hutang baru', $hutangBaru, 'Belanja kredit, produksi & hutang lain', 'text-danger'], ['bayar', 'Pembayaran hutang', $dibayar, 'Ke hutang lama: ' . $rupiah($dibayarLama), 'text-success'], ['akhir', 'Saldo hutang akhir periode', $saldoAkhir, 'Awal + baru − dibayar', 'text-danger']] as [$key, $label, $value, $hint, $color])
            <div class="col-sm-6 col-xl-3"><div class="card direktur-card h-100"><div class="card-body">
                <button type="button" class="direktur-click" data-ringkasan-url="{{ $detail($key) }}" aria-label="Lihat rincian {{ $label }}">
                    <span class="direktur-label text-muted d-block mb-3">{{ $label }}</span>
                    <span class="direktur-number d-block {{ $color }}">{{ $key === 'baru' ? '+' : ($key === 'bayar' ? '−' : '') }} {{ $rupiah($value) }}</span>
                    <span class="small text-muted d-block mt-2">{{ $hint }}</span><span class="small text-primary d-block mt-2">Lihat rincian ↗</span>
                </button>
            </div></div></div>
        @endforeach
    </div>

    <div class="row g-3 mb-4">
        <div class="col-lg-7">
            <div class="card direktur-card h-100"><div class="card-body">
                <h3 class="h6 fw-bold mb-3">Mengapa hutang tetap menumpuk?</h3>
                <button type="button" class="direktur-click d-flex justify-content-between gap-2 mb-2" data-ringkasan-url="{{ $detail('lama') }}"><span>Hutang lama yang masih terbuka ↗</span><strong>{{ $rupiah($tunggakanLama) }}</strong></button>
                <div class="progress mb-3" style="height: 12px" role="progressbar" aria-label="Porsi hutang lama" aria-valuenow="{{ $saldoAkhir > 0 ? round($tunggakanLama / $saldoAkhir * 100) : 0 }}" aria-valuemin="0" aria-valuemax="100"><div class="progress-bar bg-danger" style="width: {{ $saldoAkhir > 0 ? min(100, round($tunggakanLama / $saldoAkhir * 100)) : 0 }}%"></div></div>
                <p class="small text-muted mb-2">{{ $saldoAkhir > 0 ? number_format($tunggakanLama / $saldoAkhir * 100, 1, ',', '.') : 0 }}% saldo akhir berasal dari hutang sebelum periode. Ini bukan tanggal jatuh tempo; aplikasi belum mencatat jatuh tempo hutang.</p>
                <hr>
                <div class="row g-2">
                    @foreach (['hutang' => 'Hutang umum', 'belanja' => 'Hutang belanja', 'belanja produksi' => 'Hutang belanja produksi'] as $jenis => $label)
                        <div class="col-sm-4"><button type="button" class="direktur-click" data-ringkasan-url="{{ $detail('jenis', ['jenis' => $jenis]) }}"><span class="small text-muted d-block">{{ $label }} ↗</span><strong>{{ $rupiah($perJenis[$jenis] ?? 0) }}</strong></button></div>
                    @endforeach
                </div>
            </div></div>
        </div>
        <div class="col-lg-5">
            <div class="card direktur-card h-100"><div class="card-body">
                <h3 class="h6 fw-bold mb-3">Eksposur terbesar per kontak</h3>
                @forelse ($perKontak as $nama => $jumlah)
                    <button type="button" class="direktur-click d-flex justify-content-between gap-3 border-bottom py-2" data-ringkasan-url="{{ $detail('kontak', ['kontak' => $nama]) }}"><span>{{ $nama }} ↗</span><strong class="text-nowrap">{{ $rupiah($jumlah) }}</strong></button>
                @empty
                    <p class="text-muted mb-0">Tidak ada hutang terbuka.</p>
                @endforelse
            </div></div>
        </div>
    </div>

    <div class="card direktur-card mb-4"><div class="card-body">
        <div class="d-flex flex-wrap justify-content-between gap-2 mb-3"><div><h3 class="h6 fw-bold mb-1">Hutang terbuka paling lama</h3><small class="text-muted">Prioritas peninjauan; urut umur hutang, bukan jatuh tempo</small></div><a href="{{ route('hutang.index', ['status' => 'belum_lunas']) }}" class="small">Detail hutang →</a></div>
        <div class="table-responsive"><table class="table table-hover align-middle mb-0"><thead><tr><th>Tanggal</th><th>Kontak</th><th>Jenis</th><th>Umur</th><th class="text-end">Sisa</th><th></th></tr></thead><tbody>
            @forelse ($prioritas as $item)
                <tr><td>{{ \Carbon\Carbon::parse($item->tanggal)->format('d/m/Y') }}</td><td>{{ $item->kontak ?: 'Tanpa kontak' }}</td><td>{{ ucfirst($item->jenis) }}</td><td>{{ $item->umur }} hari</td><td class="text-end fw-semibold">{{ $rupiah($item->sisa) }}</td><td><button type="button" class="btn btn-sm btn-outline-primary" data-ringkasan-url="{{ $detail('transaksi', ['id' => $item->id]) }}">Detail ↗</button></td></tr>
            @empty
                <tr><td colspan="6" class="text-center text-muted py-4">Tidak ada hutang yang belum lunas.</td></tr>
            @endforelse
        </tbody></table></div>
    </div></div>

    <p class="small text-muted">Hutang belanja per bulan hanya menampilkan pembelian pada bulan terpilih; hutang lama tetap terhitung di halaman ini. <a href="{{ route('laporan.hutangbelanja') }}">Buka laporan hutang belanja</a>.</p>
    @include('admin.laporan.partials.ringkasan-popup')
@endsection
