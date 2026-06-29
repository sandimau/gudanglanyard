@extends('layouts.app')

@section('title')
    Permission List
@endsection

@section('content')
    <div class="bg-light rounded">
        <div class="card">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="card-title">Permissions</h5>
                    </div>
                    <a href="{{ route('permissions.create') }}" class="btn btn-primary">Add permissions</a>
                </div>
            </div>
            <div class="card-body">
                @include('layouts.includes.messages')
                <div class="table-responsive">
                    <table class="table table-striped" id="myTable">
                        <thead>
                            <tr>
                                <th scope="col">Name</th>
                                <th scope="col">Guard</th>
                                <th scope="col"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($permissions as $permission)
                                <tr>
                                    <td>{{ $permission->name }}</td>
                                    <td>{{ $permission->guard_name }}</td>
                                    <td>
                                        <div class="d-flex">
                                            <a href="{{ route('permissions.edit', $permission->id) }}"
                                                class="btn btn-info btn-sm me-1"><i class='bx bxs-edit'></i> Edit</a>
                                            <form action="{{ route('permissions.destroy', $permission->id) }}" method="post">
                                                {{ csrf_field() }}
                                                {{ method_field('delete') }}
                                                <button type="submit" onclick="return confirm('Are you sure?')" class="btn btn-danger btn-sm"><i
                                                        class='bx bxs-trash'></i> delete</button>
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
