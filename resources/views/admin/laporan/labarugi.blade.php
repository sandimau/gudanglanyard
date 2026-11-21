@extends('layouts.app')

@section('title')
    Laba Rugi
@endsection

@section('content')
    <div class="bg-light rounded">
        <div class="card">
            <div class="card-header py-2">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <h5 class="mb-0">Laporan Laba Rugi</h5>
                    <form action="{{ route('laporan.labarugi') }}" method="get" class="d-flex flex-wrap gap-2 align-items-center mb-0">
                        <label for="bulan" class="form-label mb-0">Bulan</label>
                        <input type="month" name="bulan" id="bulan" class="form-control" style="width: 190px; max-width: 100%;" value="{{ $bulan }}" required>
                        <button type="submit" class="btn btn-primary">Filter</button>
                    </form>
                </div>
            </div>
            <div class="card-body">
                @include('layouts.includes.messages')
                <p class="text-muted small mb-3">Periode {{ \Carbon\Carbon::createFromFormat('!Y-m', $bulan)->translatedFormat('F Y') }} · Nilai dalam rupiah. Penyesuaian stok masuk menambah hasil, stok keluar mengurangi hasil.</p>
                <div class="table-responsive">
                    <table class="table table-striped align-middle">
                        <thead>
                            <tr>
                                <th scope="col">Akun</th>
                                <th scope="col" class="text-end">Pendapatan / Penambah</th>
                                <th scope="col" class="text-end">Biaya / Pengurang</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>
                                    <a href="{{ route('laporan.labakotor', ['bulan' => $bulan]) }}">Omzet</a>
                                </td>
                                <td class="text-end">{{ number_format($omzet, 0, ',', '.') }}</td>
                                <td class="text-end">—</td>
                            </tr>
                            <tr>
                                <td><a href="{{ url('admin/opnames') }}?bulan={{ $bulan }}">Penyesuaian stok masuk</a></td>
                                <td class="text-end">{{ number_format($opnameMasuk, 0, ',', '.') }}</td>
                                <td class="text-end">—</td>
                            </tr>
                            <tr>
                                <td>Modal penjualan (HPP)</td>
                                <td class="text-end">—</td>
                                <td class="text-end">{{ number_format($hpp, 0, ',', '.') }}</td>
                            </tr>
                            <tr>
                                <td>Potongan marketplace</td>
                                <td class="text-end">—</td>
                                <td class="text-end">{{ number_format($total_potonganMP, 0, ',', '.') }}</td>
                            </tr>
                            <tr>
                                <td>
                                    <a href="{{ url('admin/opnames') }}?bulan={{ $bulan }}">Penyesuaian stok keluar</a>
                                </td>
                                <td class="text-end">—</td>
                                <td class="text-end">{{ number_format($opnameKeluar, 0, ',', '.') }}</td>
                            </tr>
                            <tr>
                                <td>
                                    <a href="{{ route('laporan.operasional', ['bulan' => $bulan]) }}">Operasional</a>
                                </td>
                                <td class="text-end">—</td>
                                <td class="text-end">{{ number_format($beban, 0, ',', '.') }}</td>
                            </tr>
                            <tr>
                                <td>
                                    <a href="{{ route('laporan.penggajian', ['bulan' => $bulan]) }}">Penggajian</a>
                                </td>
                                <td class="text-end">—</td>
                                <td class="text-end">{{ number_format($gaji, 0, ',', '.') }}</td>
                            </tr>
                            <tr>
                                <td>
                                    <a href="{{ route('laporan.tunjangan', ['bulan' => $bulan]) }}">Tunjangan</a>
                                </td>
                                <td class="text-end">—</td>
                                <td class="text-end">{{ number_format($tunjangan, 0, ',', '.') }}</td>
                            </tr>
                            <tr>
                                <td>
                                    <a href="{{ route('komplain.index') }}">Kerugian komplain</a>
                                </td>
                                <td class="text-end">—</td>
                                <td class="text-end">{{ number_format($total_komplain, 0, ',', '.') }}</td>
                            </tr>
                            <tr>
                                <td>
                                    @can('gagal_produksi_access')
                                        <a href="{{ route('gagalProduksi.index') }}">Kerugian gagal produksi</a>
                                    @else
                                        Kerugian gagal produksi
                                    @endcan
                                </td>
                                <td class="text-end">—</td>
                                <td class="text-end">{{ number_format($totalGagalProduksi, 0, ',', '.') }}</td>
                            </tr>
                            <tr class="table-secondary fw-bold">
                                <td>Total</td>
                                <td class="text-end">{{ number_format($omzet + $opnameMasuk, 0, ',', '.') }}</td>
                                <td class="text-end">{{ number_format($totalBiaya, 0, ',', '.') }}</td>
                            </tr>
                            <tr class="{{ $labaBersih >= 0 ? 'table-success' : 'table-danger' }} fw-bold">
                                <td>{{ $labaBersih >= 0 ? 'Laba bersih' : 'Rugi bersih' }}</td>
                                <td colspan="2" class="text-end">Rp {{ number_format(abs($labaBersih), 0, ',', '.') }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
