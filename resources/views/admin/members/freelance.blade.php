@extends('layouts.app')

@section('title')
    Data Freelance
@endsection

@section('content')
    <div class="bg-light rounded">
        <div class="card">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="card-title">Freelance</h5>
                    </div>
                    @can('member_create')
                        <a href="{{ route('freelance.create') }}" class="popup btn btn-primary"><i class='bx bx-plus-circle'></i> Add</a>
                    @endcan
                </div>
            </div>
            <div class="card-body">
                @include('layouts.includes.messages')
                <div class="table-responsive">
                    <table class=" table table-bordered table-striped table-hover">
                        <thead>
                            <tr>
                                <th>nama lengkap</th>
                                <th>lembur</th>
                                <th>umur</th>
                                <th>lama kerja</th>
                                <th>upah</th>
                                <th>lembur</th>
                                <th>upah <br>belum dibayar</th>
                                <th>gaji</th>
                                <th>whattodo</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($members as $member)
                                <tr data-entry-id="{{ $member->id }}">
                                    <td>
                                        <a class="popup"
                                            href="{{ route('members.showFreelance', $member->id) }}">{{ $member->nama_lengkap ?? '' }}</a>
                                    </td>
                                    <td>
                                        @can('lembur_access')
                                            <a class="popup" href="{{ route('members.lembur', $member->id) }}">{{ $member->countLembur }}</a>
                                        @elsecan('member_access')
                                            {{ $member->countLembur }}
                                        @endcan
                                    </td>
                                    <td>
                                        {{ $member->umur ?? '' }}
                                    </td>
                                    <td>
                                        {{ $member->lamaKerja ?? '' }}
                                    </td>
                                    <td>
                                        {{ number_format($member->upah) ?? '' }}
                                    </td>
                                    <td>
                                        {{ number_format($member->lembur) ?? '' }}
                                    </td>
                                    <td>
                                        @php
                                            $totalBelumDibayar = $member->total_upah_belum_dibayar ?? 0;
                                        @endphp
                                        @can('penggajian_access')
                                            <a class="popup"
                                                href="{{ route('members.freelanceTagihan', $member->id) }}">{{ number_format($totalBelumDibayar) }}</a>
                                        @endcan
                                    </td>
                                    <td>
                                        @can('penggajian_access')
                                            <a class="popup" href="{{ route('members.penggajianFreelance', $member->id) }}">Penggajian</a>
                                        @elsecan('member_access')
                                            -
                                        @endcan
                                    </td>
                                    <td>
                                        <a href="{{ route('whattodo.create', ['member_id' => $member->id]) }}"
                                            class="popup btn btn-info btn-sm me-1 text-white"><i class='bx bxs-add-to-queue'></i>
                                            add</a>
                                    </td>
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
