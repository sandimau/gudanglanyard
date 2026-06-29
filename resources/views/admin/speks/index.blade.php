@extends('layouts.app')

@section('title')
    Speks List
@endsection

@section('content')
    <div class="bg-light rounded">
        <div class="card">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="card-title">Speks</h5>
                    </div>
                    <a href="{{ route('speks.create') }}" class="btn btn-primary">Add speks</a>
                </div>
            </div>
            <div class="card-body">
                @include('layouts.includes.messages')
                <div class="table-responsive">
                    <table class="table table-striped" id="myTable">
                        <thead>
                            <tr>
                                <th scope="col">Nama</th>
                                <th scope="col">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($speks as $spek)
                                <tr>
                                    <td>{{ $spek->nama }}</td>
                                    <td>
                                        <div class="d-flex">
                                            <a href="{{ route('speks.edit', $spek->id) }}"
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
