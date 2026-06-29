@extends('layouts.app')

@section('title')
    Level List
@endsection

@section('content')
    <div class="bg-light rounded">
        <div class="card">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="card-title">Level</h5>
                    </div>
                    @can('level_create')
                        <a href="{{ route('level.create') }}" class="btn btn-primary ">Add level</a>
                    @endcan
                </div>
            </div>
            <div class="card-body">
                @include('layouts.includes.messages')
                <div class="table-responsive">
                    <table class="table table-striped" id="myTable">
                        <thead>
                            <tr>
                                <th scope="col">nama</th>
                                <th scope="col">gaji pokok</th>
                                <th scope="col">komunikasi</th>
                                <th scope="col">transportasi</th>
                                <th scope="col">kehadiran</th>
                                <th scope="col">lama kerja (%)</th>
                                <th scope="col">harga lembur</th>
                                <th scope="col">actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($levels as $level)
                                <tr>
                                    <td>{{ $level->nama }}</td>
                                    <td>{{ number_format($level->gaji_pokok) }}</td>
                                    <td>{{ number_format($level->komunikasi) }}</td>
                                    <td>{{ number_format($level->transportasi) }}</td>
                                    <td>{{ number_format($level->kehadiran) }}</td>
                                    <td>{{ number_format($level->lama_kerja) }}</td>
                                    <td>{{ number_format($level->harga_lembur) }}</td>
                                    <td>
                                        <div class="d-flex">
                                            <a href="{{ route('level.edit', $level->id) }}" class="btn btn-info btn-sm me-1"><i
                                                    class='bx bxs-edit'></i> Edit</a>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
