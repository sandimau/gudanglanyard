@extends('layouts.app')

@section('title')
    Tunjangan
@endsection

@section('content')
    <header class="header mb-4">
        <div class="container-fluid">
            <h5 class="card-title">Tunjangan</h5>
        </div>
    </header>
    <div class="bg-light rounded">
        <div class="card">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <form action="{{ route('laporan.tunjangan') }}" method="get" class="d-flex gap-2 align-items-center">

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
                {{ $tunjangans->links() }}
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Tanggal</th>
                                <th>Member</th>
                                <th>Tunjangan</th>
                                <th>Keterangan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($tunjangans as $tunjangan)
                                <tr>
                                    <td>{{ $tunjangan->created_at->format('d-m-Y') }}</td>
                                    <td>{{ $tunjangan->member->nama_lengkap }}</td>
                                    <td>{{ $tunjangan->jumlah }}</td>
                                    <td>{{ $tunjangan->ket }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
