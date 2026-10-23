@extends('layouts.app')

@section('title')
Edit Role
@endsection

@section('content')
<div class="bg-light rounded">
    <div class="card">
        <div class="card-body">
            <h5 class="card-title">Update role</h5>
            <div class="container mt-4">

                <form method="POST" action="{{ route('roles.update', $role->id) }}">
                    @method('patch')
                    @csrf
                    <div class="mb-3">
                        <label for="name" class="form-label">Name</label>
                        <input value="{{ old('name', $role->name) }}" type="text" class="form-control" name="name" placeholder="Name"
                            @if ($role->name === 'konten') readonly @endif
                            required>
                        @error('name') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                    </div>

                    <label for="permissions" class="form-label">Assign Permissions</label>
                    @php
                        $selectedPermissions = old('_token')
                            ? array_values((array) old('permission', []))
                            : $rolePermissions;
                    @endphp

                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <th scope="col" width="1%"><input type="checkbox" name="all_permission"></th>
                                <th scope="col" width="20%">Name</th>
                                <th scope="col" width="1%">Guard</th>
                            </thead>

                            @forelse($permissions as $permission)
                            <tr>
                                <td>
                                    <input type="checkbox" name="permission[{{ $permission->name }}]"
                                        value="{{ $permission->name }}" class='permission' {{ in_array($permission->name, $selectedPermissions, true)
                                        ? 'checked'
                                        : '' }}>
                                </td>
                                <td>{{ [
                                    'konten_jadwal_access' => 'Jadwal',
                                    'konten_proses_access' => 'Proses Konten',
                                ][$permission->name] ?? $permission->name }}</td>
                                <td>{{ $permission->guard_name }}</td>
                            </tr>
                            @empty
                            <tr><td colspan="3" class="text-muted">Belum ada permission yang tersedia.</td></tr>
                            @endforelse
                        </table>
                    </div>
                    @error('permission') <div class="text-danger small mb-2">{{ $message }}</div> @enderror
                    @error('permission.*') <div class="text-danger small mb-2">{{ $message }}</div> @enderror

                    <button type="submit" class="btn btn-primary">Save changes</button>
                    <a href="{{ route('roles.index') }}" class="btn btn-default">Back</a>
                </form>
            </div>

        </div>
    </div>
</div>
@endsection

@push('after-scripts')
<script type="text/javascript">
$(document).ready(function() {
    $('[name="all_permission"]').on('click', function() {

        if ($(this).is(':checked')) {
            $.each($('.permission'), function() {
                $(this).prop('checked', true);
            });
        } else {
            $.each($('.permission'), function() {
                $(this).prop('checked', false);
            });
        }

    });
});
</script>
@endpush
