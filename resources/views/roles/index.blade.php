@extends('layouts.app')

@section('title')
    Role list
@endsection

@section('content')
    <div class="bg-light rounded">
        <div class="card">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="card-title mb-0">Roles</h5>
                    </div>
                    <a href="{{ route('roles.create') }}" class="btn btn-primary">Add role</a>
                </div>
            </div>
            <div class="card-body">
                @include('layouts.includes.messages')
                <div class="table-responsive">
                    <table class="table table-striped" id="myTable">
                        <thead>
                            <tr>
                                <th scope="col">No</th>
                                <th scope="col">Name</th>
                                <th scope="col">Permissions</th>
                                <th scope="col">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($roles as $role)
                                <tr>
                                    <td>{{ $role->id }}</td>
                                    <td>{{ $role->name }}</td>
                                    <td>
                                        @forelse ($role->permissions as $perm)
                                            <span class="badge text-bg-info">{{ $perm->name }}</span>
                                        @empty
                                            <span class="text-muted">—</span>
                                        @endforelse
                                    </td>
                                    <td>
                                        <div class="d-flex gap-1">
                                            <a href="{{ route('roles.show', $role->id) }}"
                                                class="btn btn-warning btn-sm"><i class='bx bx-plus-circle'></i> Show</a>
                                            <a href="{{ route('roles.edit', $role->id) }}"
                                                class="btn btn-info btn-sm"><i class='bx bxs-edit'></i> Edit</a>
                                            <form action="{{ route('roles.destroy', $role->id) }}" method="post">
                                                @csrf
                                                @method('delete')
                                                <button type="submit" onclick="return confirm('Are you sure?')"
                                                    class="btn btn-danger btn-sm"><i class='bx bxs-trash'></i> Delete</button>
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
        new DataTable('#myTable', {
            pageLength: 10,
            order: [[0, 'asc']],
            columnDefs: [
                { orderable: false, targets: 3 },
            ],
        });
    </script>
@endpush
