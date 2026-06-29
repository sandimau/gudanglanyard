@extends('layouts.app')

@section('title')
    Data Member Kasbon
@endsection

@section('content')
    <div class="bg-light rounded">
        @include('layouts.includes.messages')
        <div class="card">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="card-title">Kasbon</h5>
                    </div>
                    @can('kasbon_create')
                        <div class="d-flex gap-1">
                            <a href="{{ route('kasbon.create', $member->id) }}" class="popup btn btn-success text-white"><i
                                    class='bx bxs-edit'></i> tambah kasbon</a>
                            @php
                                $latestKasbon = $member->kasbon()->latest('id')->first();
                            @endphp
                            @if ($latestKasbon && $latestKasbon->saldo > 0)
                                <a href="{{ route('kasbon.bayar', $member->id) }}" class="popup btn btn-primary text-white"><i
                                        class='bx bxs-edit'></i> bayar kasbon</a>
                            @endif
                        </div>
                    @endcan
                </div>
            </div>
            <div class="card-body">
                {{ $kasbons->links() }}
                <div class="table-responsive">
                    <table class="table table-bordered table-striped table-hover">
                        <thead>
                            <tr>
                                <th>
                                    Tanggal
                                </th>
                                <th>
                                    ket
                                </th>
                                <th>
                                    kasbon
                                </th>
                                <th>
                                    pembayaran
                                </th>
                                <th>
                                    saldo
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($kasbons as $item)
                                <tr>
                                    <td>{{ $item->created_at }}</td>
                                    <td>{{ $item->keterangan }}</td>
                                    <td>{{ number_format($item->pemasukan) }}</td>
                                    <td>{{ number_format($item->pengeluaran) }}</td>
                                    <td>{{ number_format($item->saldo) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
