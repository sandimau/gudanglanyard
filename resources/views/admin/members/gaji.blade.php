@extends('layouts.app')

@section('title')
    Data Member Gaji
@endsection

@section('content')
    <div class="bg-light rounded">
        @include('layouts.includes.messages')
        <div class="card mt-4">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="card-title">Gaji</h5>
                    </div>
                    <a href="{{ route('gaji.create', $member->id) }}" class="popup btn btn-success text-white"><i
                            class='bx bxs-edit'></i> add gaji</a>
                </div>
            </div>
            <div class="card-body">
                {{ $gajis->links() }}
                <div class="table-responsive">
                    <table class="table table-bordered table-striped table-hover">
                        <thead>
                            <tr>
                                <th>
                                    Tanggal
                                </th>
                                <th>
                                    Bagian
                                </th>
                                <th>
                                    Level
                                </th>
                                <th>
                                    Performance
                                </th>
                                <th>
                                    Transportasi
                                </th>
                                <th>
                                    Tunjangan Lain
                                </th>
                                <th>
                                    Nilai Tunjangan Lain
                                </th>
                                <th>total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($gajis as $item)
                                <tr>
                                    <td>{{ $item->created_at }}</td>
                                    <td>{{ $item->bagian->nama?? '-' }}</td>
                                    <td>{{ $item->level->nama?? '-' }}</td>
                                    <td>{{ $item->performance }}</td>
                                    <td>{{ $item->transportasi == 1 ? 'ya' : 'tidak' }}</td>
                                    <td>{{ $item->lain_lain }}</td>
                                    <td>{{ number_format($item->jumlah_lain) }}</td>
                                    <td>{{ number_format($item->total) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
