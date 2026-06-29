@extends('layouts.app')

@section('title')
    Pemproses List
@endsection

@section('content')
    <div class="bg-light rounded">
        <div class="card">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="card-title">Pemproses</h5>
                    </div>
                    <a href="{{ route('pemproses.create') }}" class="btn btn-primary">Tambah Pemproses</a>
                </div>
            </div>
            <div class="card-body">
                @include('layouts.includes.messages')
                <div class="table-responsive">
                    <table class="table table-striped" id="myTable">
                        <thead>
                            <tr>
                                <th scope="col">Nama</th>
                                <th scope="col">Warna</th>
                                <th scope="col">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($pemproses as $item)
                                <tr>
                                    <td>{{ $item->nama }}</td>
                                    <td>
                                        @if ($item->warna)
                                            <span class="badge rounded-pill"
                                                style="background-color: #{{ ltrim($item->warna, '#') }}">&nbsp;&nbsp;&nbsp;</span>
                                            {{ $item->warna }}
                                        @endif
                                    </td>
                                    <td>
                                        <div class="d-flex">
                                            <a href="{{ route('pemproses.edit', $item->id) }}"
                                                class="btn btn-info btn-sm me-1"><i class='bx bxs-edit'></i> Edit</a>
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
