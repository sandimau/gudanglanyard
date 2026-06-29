@extends('layouts.app')

@section('title')
    Marketplace Analisa
@endsection

@section('content')
    <div class="bg-light rounded">
        <div class="card">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <form action="{{ route('marketplaces.analisa') }}" method="GET" class="d-flex gap-2 align-items-center">
                        <label for="bulan" class="form-label mb-0">Bulan</label>
                        <select name="bulan" id="bulan" class="form-control">
                            @foreach ($listBulan as $key => $value)
                                <option value="{{ $key }}" {{ $key == $bulanParam ? 'selected' : '' }}>{{ $value }}</option>
                            @endforeach
                        </select>
                        <button type="submit" class="btn btn-primary">Cari</button>
                    </form>
                </div>
            </div>
            <div class="card-body">
                @include('layouts.includes.messages')
                <p class="text-muted mb-3">Jumlah data: {{ $rows->count() }}</p>
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th scope="col">Nama</th>
                                <th scope="col" class="text-end">Pendapatan MP</th>
                                <th scope="col" class="text-end">HPP</th>
                                <th scope="col" class="text-end">Fee MP</th>
                                <th scope="col" class="text-end">Iklan</th>
                                <th scope="col" class="text-end">total biaya</th>
                                <th scope="col" class="text-end">biaya %</th>
                                <th scope="col" class="text-end">Keuntungan</th>
                                <th scope="col" class="text-end">margin %</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($rows as $row)
                                @php $mp = $row->marketplace; @endphp
                                <tr>
                                    <td>{{ $mp->nama }}</td>
                                    <td class="text-end">
                                        @if($row->pendapatan_mp > 0)
                                            @if(strtolower($mp->marketplace ?? '') === 'shopee')
                                                <a href="{{ route('projectmp.index', [
                                                    'dari' => $row->tanggal_awal,
                                                    'sampai' => $row->tanggal_akhir,
                                                    'marketplace_id' => $mp->id,
                                                    'pembayaran' => 0,
                                                ]) }}" class="text-decoration-none">
                                                    {{ number_format($row->pendapatan_mp, 2, ',', '.') }}
                                                </a>
                                            @else
                                                <a href="{{ route('order.marketplace', [
                                                    'nota' => '',
                                                    'kontak_id' => $mp->kontak->id,
                                                    'produk_id' => '',
                                                    'dari' => $row->tanggal_awal,
                                                    'sampai' => $row->tanggal_akhir,
                                                ]) }}" class="text-decoration-none">
                                                    {{ number_format($row->pendapatan_mp, 2, ',', '.') }}
                                                </a>
                                            @endif
                                        @else
                                            {{ number_format($row->pendapatan_mp, 2, ',', '.') }}
                                        @endif
                                    </td>
                                    <td class="text-end">{{ number_format($row->hpp, 2, ',', '.') }}</td>
                                    <td class="text-end">{{ number_format($row->fee_mp, 2, ',', '.') }}</td>
                                    <td class="text-end">{{ number_format($row->iklan, 2, ',', '.') }}</td>
                                    <td class="text-end">{{ number_format($row->total_biaya, 2, ',', '.') }}</td>
                                    <td class="text-end">{{ $row->biaya_persen }}</td>
                                    <td class="text-end">{{ number_format($row->keuntungan, 2, ',', '.') }}</td>
                                    <td class="text-end">{{ $row->margin_persen }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr class="fw-bold">
                                <td>total</td>
                                <td class="text-end">{{ number_format($totals->pendapatan_mp, 2, ',', '.') }}</td>
                                <td class="text-end">{{ number_format($totals->hpp, 2, ',', '.') }}</td>
                                <td class="text-end">{{ number_format($totals->fee_mp, 2, ',', '.') }}</td>
                                <td class="text-end">{{ number_format($totals->iklan, 2, ',', '.') }}</td>
                                <td class="text-end">{{ number_format($totals->total_biaya, 2, ',', '.') }}</td>
                                <td class="text-end">{{ $totals->biaya_persen }}</td>
                                <td class="text-end">{{ number_format($totals->keuntungan, 2, ',', '.') }}</td>
                                <td class="text-end">{{ $totals->margin_persen }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
