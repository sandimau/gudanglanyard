@extends('layouts.app')

@section('title')
    Laporan Operasional
@endsection

@section('content')
    <div class="bg-light rounded">
        <div class="card">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <form action="{{ route('laporan.operasional') }}" method="get" class="d-flex gap-2 align-items-center">
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
                                <th>Kategori Utama</th>
                                <th>Kategori</th>
                                <th>Total Belanja</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php
                                $totalBelanja = 0;
                                $currentKategoriUtama = '';
                                $subTotalBelanja = 0;
                            @endphp

                            @foreach($data as $item)
                                @if($currentKategoriUtama != $item->kategori_utama)
                                    @if(!$loop->first)
                                        <tr class="table-secondary">
                                            <td colspan="2"><strong>Sub Total {{ $currentKategoriUtama }}</strong></td>
                                            <td><strong>{{ number_format($subTotalBelanja, 0, ',', '.') }}</strong></td>
                                        </tr>
                                    @endif
                                    @php
                                        $currentKategoriUtama = $item->kategori_utama;
                                        $subTotalBelanja = 0;
                                    @endphp
                                @endif

                                <tr>
                                    <td>{{ $item->kategori_utama }}</td>
                                    <td>
                                        @if($view_type == 'kategori')
                                            <a href="{{ url('admin/operasionaldetail') }}?bulan={{ request('bulan') ?? date('Y-m') }}&kategori={{ $item->kategori_id }}">
                                                {{ $item->kategori }}
                                            </a>
                                        @else
                                            {{ $item->produk }}
                                        @endif
                                    </td>
                                    <td>{{ number_format($item->total_belanja, 0, ',', '.') }}</td>
                                </tr>

                                @php
                                    $totalBelanja += $item->total_belanja;
                                    $subTotalBelanja += $item->total_belanja;
                                @endphp

                                @if($loop->last)
                                    <tr class="table-secondary">
                                        <td colspan="2"><strong>Sub Total {{ $currentKategoriUtama }}</strong></td>
                                        <td><strong>{{ number_format($subTotalBelanja, 0, ',', '.') }}</strong></td>
                                    </tr>
                                @endif
                            @endforeach

                            <tr class="table-primary">
                                <td colspan="2"><strong>Total Keseluruhan</strong></td>
                                <td><strong>{{ number_format($totalBelanja, 0, ',', '.') }}</strong></td>
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
        $(document).ready(function() {
            $('#bulan').on('change', function() {
                $(this).closest('form').submit();
            });
        });
    </script>
@endpush
