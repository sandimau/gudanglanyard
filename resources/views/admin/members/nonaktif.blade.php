@extends('layouts.app')

@section('title')
    Data Member
@endsection

@section('content')
    <div class="bg-light rounded">
        <div class="card">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="card-title">Members Nonaktif</h5>
                    </div>
                    <a href="{{ route('members.index') }}" class="btn btn-primary" data-modal-skip><i class='bx bx-arrow-back'></i> aktif</a>
                </div>
            </div>
            <div class="card-body">
                @include('layouts.includes.messages')
                <div class="table-responsive">
                    <table class="table table-bordered table-striped table-hover">
                        <thead>
                            <tr>
                                <th>
                                    nama lengkap
                                </th>
                                <th>
                                    tgl masuk
                                </th>
                                <th>
                                    tgl keluar
                                </th>
                                <th>
                                    tgl lahir
                                </th>
                                <th>
                                    tempat lahir
                                </th>
                                <th>
                                    alamat
                                </th>
                                <th>
                                    hp
                                </th>
                                <th>
                                    umur
                                </th>
                                <th>
                                    lama kerja
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($members as $member)
                                <tr data-entry-id="{{ $member->id }}">
                                    <td>
                                        <a class="popup" href="{{ route('members.show', $member->id) }}">{{ $member->nama_lengkap ?? '' }}</a>
                                    </td>
                                    <td>{{ $member->tgl_masuk }}</td>
                                    <td>{{ $member->tgl_keluar }}</td>
                                    <td>{{ $member->tgl_lahir }}</td>
                                    <td>{{ $member->tempat_lahir }}</td>
                                    <td>{{ $member->alamat }}</td>
                                    <td>{{ $member->no_telp }}</td>
                                    <td>{{ $member->umur ?? '' }}</td>
                                    <td>{{ $member->lamaKerja ?? '' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    @include('admin.members.partials.detail-member-modal')
@endsection

@push('after-scripts')
    <script>
        @include('admin.members.partials.detail-member-modal-js')
    </script>
    <style>
        @include('admin.members.partials.detail-member-modal-styles')
    </style>
@endpush
