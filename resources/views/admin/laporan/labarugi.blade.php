@extends('layouts.app')

@section('title')
    Laba Rugi
@endsection

@section('content')
    <div class="bg-light rounded">
        <div class="card">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <form action="{{ route('laporan.labarugi') }}" method="get" class="d-flex gap-2 align-items-center">
                        <label for="bulan" class="form-label mb-0">Bulan</label>
                        <select name="bulan" id="bulan" class="form-control">
                            @foreach ($bulan as $key => $value)
                                <option value="{{ $key }}" {{ $key == (request('bulan') ?? date('Y-m')) ? 'selected' : '' }}>{{ $value }}</option>
                            @endforeach
                        </select>
                        <button type="submit" class="btn btn-primary">Filter</button>
                    </form>
                </div>
            </div>
            <div class="card-body">
                @include('layouts.includes.messages')
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th scope="col">Akun</th>
                                <th scope="col">Debit</th>
                                <th scope="col">Kredit</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>
                                    <a href="{{ url('admin/labakotor') }}?bulan={{ request('bulan') ?? date('Y-m') }}">omzet</a>
                                </td>
                                <td>{{ number_format($omzet, 0, ',', '.') }}</td>
                                <td>0</td>
                            </tr>
                            <tr>
                                <td>modal penjualan</td>
                                <td>0</td>
                                <td>{{ number_format(abs($hpp), 0, ',', '.') }}</td>
                            </tr>
                            <tr>
                                <td>potongan marketplace</td>
                                <td>0</td>
                                <td>{{ number_format($total_potonganMP, 0, ',', '.') }}</td>
                            </tr>
                            <tr>
                                <td>
                                    <a href="{{ url('admin/opnames') }}?bulan={{ request('bulan') ?? date('Y-m') }}">opname</a>
                                </td>
                                <td>0</td>
                                <td>{{ number_format($opname, 0, ',', '.') }}</td>
                            </tr>
                            <tr>
                                <td>
                                    <a href="{{ url('admin/operasional') }}?bulan={{ request('bulan') ?? date('Y-m') }}">operasional</a>
                                </td>
                                <td>0</td>
                                <td>{{ number_format($beban, 0, ',', '.') }}</td>
                            </tr>
                            <tr>
                                <td>
                                    <a href="{{ url('admin/penggajian') }}?bulan={{ request('bulan') ?? date('Y-m') }}">penggajian</a>
                                </td>
                                <td>0</td>
                                <td>{{ number_format($gaji, 0, ',', '.') }}</td>
                            </tr>
                            <tr>
                                <td>
                                    <a href="{{ url('admin/tunjangan') }}?bulan={{ request('bulan') ?? date('Y-m') }}">tunjangan</a>
                                </td>
                                <td>0</td>
                                <td>{{ number_format($tunjangan, 0, ',', '.') }}</td>
                            </tr>
                            <tr class="table-success">
                                <td>saldo</td>
                                <td>0</td>
                                <td>{{ number_format($omzet - abs($hpp) - $opname - $beban - $gaji - $tunjangan - $total_potonganMP, 0, ',', '.') }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
@push('after-scripts')
    <script>
        let table = new DataTable('#myTable');
    </script>
@endpush
