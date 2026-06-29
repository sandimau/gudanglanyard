@extends('layouts.app')

@section('title')
    Marketplace List
@endsection

@section('content')
    <div class="bg-light rounded">
        <div class="card">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="card-title">Marketplace</h5>
                    </div>
                    @can('marketplace_create')
                        <a href="{{ route('marketplaces.create') }}" class="btn btn-primary ">Add marketplace</a>
                    @endcan
                </div>
            </div>
            <div class="card-body">
                @include('layouts.includes.messages')
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th scope="col">nama</th>
                                <th scope="col">shop_id</th>
                                <th scope="col">marketplace</th>
                                <th scope="col">kas marketplace</th>
                                <th scope="col">kas penarikan</th>
                                <th scope="col">konsumen</th>
                                <th scope="col">produk Iklan</th>
                                @can('marketplace_edit')
                                    <th scope="col">sinkron</th>
                                    <th scope="col">actions</th>
                                @endcan
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($marketplaces as $marketplace)
                                <tr>
                                    <td><a
                                            href="{{ route('marketplaces.show', $marketplace->id) }}">{{ $marketplace->nama }}</a>
                                    </td>
                                    <td>{{ $marketplace->shop_id ?? '-' }}</td>
                                    <td>{{ $marketplace->marketplace }}</td>
                                    <td>{{ $marketplace->kas->nama ?? '-' }}</td>
                                    <td>{{ $marketplace->kasPenarikan->nama ?? '-' }}</td>
                                    <td>{{ $marketplace->kontak->nama ?? '-' }}</td>
                                    <td>{{ $marketplace->produk->namaLengkap ?? '-' }}</td>
                                    @can('marketplace_edit')
                                        <td>{!! $marketplace->sinkron !!}</td>
                                        <td>
                                            <div class="d-flex">
                                                <a href="{{ route('marketplaces.edit', $marketplace->id) }}"
                                                    class="btn btn-info btn-sm me-1"><i class='bx bxs-edit'></i></a>
                                                <form action="{{ route('marketplaces.destroy', $marketplace->id) }}"
                                                    method="post">
                                                    {{ csrf_field() }}
                                                    {{ method_field('delete') }}
                                                    <button type="submit" onclick="return confirm('Are you sure?')"
                                                        class="btn btn-danger btn-sm"><i class='bx bxs-trash'></i></button>
                                                </form>
                                            </div>
                                        </td>
                                    @endcan
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
