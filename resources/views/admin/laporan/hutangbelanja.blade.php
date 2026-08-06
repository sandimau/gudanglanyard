@extends('layouts.app')

@section('title')
    Laporan Hutang Belanja
@endsection

@section('content')
    <div class="bg-light rounded">
        <div class="card">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <form action="{{ route('laporan.hutangbelanja') }}" method="get" class="d-flex gap-2 align-items-center">
                        <label for="bulan" class="form-label mb-0">Bulan</label>
                        <select name="bulan" id="bulan" class="form-control">
                            @foreach ($bulan as $key => $value)
                                <option value="{{ $key }}" {{ $key == $bulanParam ? 'selected' : '' }}>{{ $value }}</option>
                            @endforeach
                        </select>
                        <button type="submit" class="btn btn-primary">Filter</button>
                    </form>

                    <a href="{{ route('laporan.labarugi', ['bulan' => $bulanParam]) }}" class="btn btn-secondary">
                        Kembali
                    </a>
                </div>
            </div>
            <div class="card-body">
                @include('layouts.includes.messages')

                <div class="row mb-3">
                    <div class="col-md-4">
                        <div class="card bg-primary bg-opacity-10 border-primary h-100">
                            <div class="card-body py-2">
                                <div class="text-primary small">Total Belanja</div>
                                <div class="fs-5 fw-bold text-primary">Rp {{ number_format($totalBelanja, 0, ',', '.') }}</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-success bg-opacity-10 border-success h-100">
                            <div class="card-body py-2">
                                <div class="text-success small">Belanja Sudah Dibayar</div>
                                <div class="fs-5 fw-bold text-success">Rp {{ number_format($totalDibayar, 0, ',', '.') }}</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-danger bg-opacity-10 border-danger h-100">
                            <div class="card-body py-2">
                                <div class="text-danger small">Belanja Masih Hutang</div>
                                <div class="fs-5 fw-bold text-danger">Rp {{ number_format($totalHutang, 0, ',', '.') }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Tanggal</th>
                                <th>Nota</th>
                                <th>Kontak</th>
                                <th>Total Belanja</th>
                                <th>Sudah Dibayar</th>
                                <th>Sisa Hutang</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($belanjas as $item)
                                <tr>
                                    <td>{{ \Carbon\Carbon::parse($item->tanggal_beli)->format('d/m/Y') }}</td>
                                    <td>{{ $item->nota }}</td>
                                    <td>{{ $item->kontak->nama ?? '-' }}</td>
                                    <td>Rp {{ number_format($item->total, 0, ',', '.') }}</td>
                                    <td>Rp {{ number_format($item->total - $item->sisa_hutang, 0, ',', '.') }}</td>
                                    <td>Rp {{ number_format($item->sisa_hutang, 0, ',', '.') }}</td>
                                    <td>
                                        @if ($item->sisa_hutang > 0)
                                            <span class="badge bg-warning">Belum Lunas</span>
                                        @else
                                            <span class="badge bg-success">Lunas</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center">Tidak ada data belanja bulan ini</td>
                                </tr>
                            @endforelse
                        </tbody>
                        <tfoot>
                            <tr class="table-primary">
                                <td colspan="3"><strong>Total</strong></td>
                                <td><strong>Rp {{ number_format($totalBelanja, 0, ',', '.') }}</strong></td>
                                <td><strong>Rp {{ number_format($totalDibayar, 0, ',', '.') }}</strong></td>
                                <td><strong>Rp {{ number_format($totalHutang, 0, ',', '.') }}</strong></td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('after-scripts')
    <script>
        $(document).ready(function() {
            $('#bulan').on('change', function() {
                $(this).closest('form').submit();
            });
        });
    </script>
@endpush
