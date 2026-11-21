@extends('layouts.app')

@section('title')
    Neraca
@endsection

@section('content')
    <div class="bg-light rounded">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Ringkasan Posisi Keuangan</h5>
            </div>
            <div class="card-body">
                @include('layouts.includes.messages')
                <p class="text-muted small mb-3">Posisi saat ini · Gabungan seluruh cabang. Stok dinilai dari HPP produk, bukan harga jual. Piutang order hanya mencakup order non-marketplace yang belum lunas.</p>
                <div class="table-responsive">
                    <table class="table table-striped align-middle">
                        <thead>
                            <tr>
                                <th scope="col">Akun</th>
                                <th scope="col" class="text-end">Nilai (Rp)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr class="table-light fw-bold"><td colspan="2">Aset</td></tr>
                            <tr><td>Kas</td><td class="text-end">{{ number_format($kas, 0, ',', '.') }}</td></tr>
                            <tr>
                                <td>Persediaan (HPP)</td>
                                <td class="text-end">{{ number_format($stok, 0, ',', '.') }}</td>
                            </tr>
                            <tr>
                                <td>Piutang order non-marketplace</td>
                                <td class="text-end">{{ number_format($total_order, 0, ',', '.') }}</td>
                            </tr>
                            <tr>
                                <td>Piutang lainnya</td>
                                <td class="text-end">{{ number_format($total_piutang, 0, ',', '.') }}</td>
                            </tr>
                            <tr class="table-primary fw-bold"><td>Total aset</td><td class="text-end">{{ number_format($totalAset, 0, ',', '.') }}</td></tr>
                            <tr class="table-light fw-bold"><td colspan="2">Kewajiban dan modal</td></tr>
                            <tr>
                                <td>Hutang (termasuk belanja produksi dan aset)</td>
                                <td class="text-end">{{ number_format($total_hutang, 0, ',', '.') }}</td>
                            </tr>
                            <tr>
                                <td>Modal</td>
                                <td class="text-end">{{ number_format(abs($modal), 0, ',', '.') }}</td>
                            </tr>
                            <tr class="{{ abs($selisih) < 1 ? 'table-success' : 'table-warning' }} fw-bold">
                                <td>Selisih belum direkonsiliasi</td>
                                <td class="text-end">{{ number_format($selisih, 0, ',', '.') }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <p class="text-muted small mb-0">Selisih = total aset − hutang − modal. Angka ini bukan laba periode; lihat <a href="{{ route('laporan.labarugi') }}">laporan laba rugi</a> untuk hasil usaha bulanan. Piutang yang dicatat juga di order mungkin masih perlu direkonsiliasi agar tidak dihitung dua kali.</p>
            </div>
        </div>
    </div>
@endsection
