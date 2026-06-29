@extends('layouts.app')

@section('title')
    Akun Details List
@endsection

@section('content')
    <div class="bg-light rounded">
        <div class="card">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="card-title">Kas</h5>
                    </div>
                    @can('akun_kategori_create')
                        <a href="{{ route('akunDetails.create') }}" class="btn btn-primary ">Add kas</a>
                    @endcan
                </div>
            </div>
            <div class="card-body">
                @include('layouts.includes.messages')
                <div class="table-responsive">
                    <table class="table table-striped" id="myTable">
                        <thead>
                            <tr>
                                <th scope="col">id</th>
                                <th scope="col">nama</th>
                                <th scope="col">kategori</th>
                                <th scope="col">saldo</th>
                                <th scope="col">actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($akunDetails as $akun)
                                <tr>
                                    <td>{{ $akun->id }}</td>
                                    <td><a href="{{ route('akundetail.bukubesar', $akun->id) }}">{{ $akun->nama }}</a>
                                    </td>
                                    <td>{{ $akun->akun_kategori->nama }}</td>
                                    <td>{{ number_format($akun->saldo) }}</td>
                                    @can('akun_edit')
                                        <td>
                                            <div class="d-flex">
                                                <a href="{{ route('akunDetails.edit', $akun->id) }}"
                                                    class="btn btn-info btn-sm me-1"><i class='bx bxs-edit'></i> Edit</a>
                                                <form action="{{ route('akunDetails.destroy', $akun->id) }}" method="post">
                                                    {{ csrf_field() }}
                                                    {{ method_field('delete') }}
                                                    <button type="submit" onclick="return confirm('Are you sure?')"
                                                        class="btn btn-danger btn-sm"><i class='bx bxs-trash'></i>
                                                        delete</button>
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
