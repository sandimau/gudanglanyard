@extends('layouts.app')

@section('title')
    Data Penggajian
@endsection

@section('content')
    <header class="header mb-4">
        <div class="container-fluid">
            <h5 class="card-title">Penggajian</h5>
        </div>
    </header>
    <div class="bg-light rounded">
        <div class="card">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <form action="{{ route('laporan.penggajian') }}" method="get" class="d-flex gap-2 align-items-center">
                        <label for="tanggal" class="form-label mb-0">Dari</label>
                        <input type="date" name="dari" class="form-control" value="{{ $dari ?? date('Y-m-d') }}">
                        <label for="tanggal" class="form-label mb-0">Sampai</label>
                        <input type="date" name="sampai" class="form-control" value="{{ $sampai ?? date('Y-m-d') }}">
                        <button type="submit" class="btn btn-primary">Filter</button>
                    </form>
                </div>
            </div>
            <div class="card-body">
                @include('layouts.includes.messages')
                {{ $penggajians->links() }}
                <div class="table-responsive">
                    <table class="table table-bordered table-striped table-hover">
                        <thead>
                            <tr>
                                <th>
                                    tanggal
                                </th>
                                <th>
                                    pegawai
                                </th>
                                <th>
                                    gapok
                                </th>
                                <th>
                                    jam lembur
                                </th>
                                <th>
                                    lembur
                                </th>
                                <th>
                                    kasbon
                                </th>
                                <th>
                                    bonus
                                </th>
                                <th>
                                    total
                                </th>
                                <th>
                                    print
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($penggajians as $item)
                                <tr>
                                    <td>{{ date('d-m-Y', strtotime($item->created_at)) }}</td>
                                    <td>{{ $item->member->nama_lengkap }}</td>
                                    <td>{{ number_format($item->pokok) }}</td>
                                    <td>{{ $item->jam_lembur }}</td>
                                    <td>{{ number_format($item->lembur) }}</td>
                                    <td>{{ number_format($item->kasbon) }}</td>
                                    <td>{{ number_format($item->bonus) }}</td>
                                    <td>{{ number_format($item->total) }}</td>
                                    <td><a href="{{ route('penggajian.slip', $item->id) }}"
                                            class="btn btn-primary btn-sm">slip gaji</a></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
