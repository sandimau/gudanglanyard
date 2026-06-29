@extends('layouts.app')

@section('title')
    Data Kontaks
@endsection

@section('content')
    <div class="bg-light rounded">
        <div class="card">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="card-title">Kontaks</h5>
                    </div>
                    @can('kontak_create')
                        <a href="{{ route('kontaks.create') }}" class="btn btn-primary">Add kontaks</a>
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
                                <th scope="col">perusahaan</th>
                                <th scope="col">No Telp</th>
                                <th scope="col">Lama Gabung(bln)</th>
                                <th scope="col">Order Terakhir(bln)</th>
                                <th scope="col">Total Omzet</th>
                                <th scope="col">Omzet/Bln</th>
                                <th scope="col">Jenis</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($kontaks as $kontak)
                                <tr>
                                    <td><a href="{{ route('kontaks.show',$kontak->id) }}">{{ $kontak->nama }}</a></td>
                                    <td>{{ $kontak->perusahaan }}</td>
                                    <td>{{ $kontak->noTelp }}</td>
                                    <td>{{ $kontak->bergabung }}</td>
                                    <td>{{ $kontak->lastOrder }}</td>
                                    <td>{{ number_format($kontak->allOmzet) }}</td>
                                    <td>{{ number_format($kontak->monthOmzet) }}</td>
                                    <td>{!! html_entity_decode($kontak->jenis) !!}</td>
                                </tr>
                            @endforeach
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
