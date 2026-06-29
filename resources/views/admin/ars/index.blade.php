@extends('layouts.app')

@section('title')
    AR List
@endsection

@section('content')
    <div class="bg-light rounded">
        <div class="card">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="card-title">Ars</h5>
                    </div>
                    @can('ar_create')
                        <a href="{{ route('ars.create') }}" class="btn btn-primary ">Add ar</a>
                    @endcan
                </div>
            </div>
            <div class="card-body">
                @include('layouts.includes.messages')
                <div class="table-responsive">
                    <table class="table table-striped" id="myTable">
                        <thead>
                            <tr>
                                <th scope="col">#</th>
                                <th scope="col">Nama</th>
                                <th scope="col">Kode</th>
                                <th scope="col">warna</th>
                                <th scope="col">ttd</th>
                                <th scope="col">actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($ars as $ar)
                                <tr>
                                    <td>{{ $ar->id }}</td>
                                    <td>{{ $ar->member->nama_lengkap }}</td>
                                    <td>{{ $ar->kode }}</td>
                                    <td style="background-color: {{ $ar->warna }}; color: #fff;">warna</td>
                                    <td>
                                        @if ($ar->ttd)
                                            <img style="height: 60px" src="{{ url('uploads/ttd/' . $ar->ttd) }}"
                                                alt="" srcset="">
                                        @endif
                                    </td>
                                    <td>
                                        <div class="d-flex">
                                            <a href="{{ route('ars.edit', $ar->id) }}" class="btn btn-info btn-sm me-1"><i
                                                    class='bx bxs-edit'></i> Edit</a>
                                            <form action="{{ route('ars.destroy', $ar->id) }}" method="post">
                                                {{ csrf_field() }}
                                                {{ method_field('delete') }}
                                                <button type="submit" onclick="return confirm('Are you sure?')"
                                                    class="btn btn-danger btn-sm"><i class='bx bxs-trash' ></i> delete</button>
                                            </form>
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
@push('after-scripts')
    <script>
        let table = new DataTable('#myTable');
    </script>
@endpush
