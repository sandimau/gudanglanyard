@extends('layouts.app')

@section('title', 'Detail Desain')

@section('content')
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <h4 class="mb-0">Detail Desain</h4>
        <a href="{{ route('desain.proses') }}" class="btn btn-outline-secondary">Kembali ke Proses</a>
    </div>
    @include('layouts.includes.messages')
    @include('admin.desain.partials.detail')
    @include('admin.desain.partials.edit-modal', ['modalAsal' => 'detail'])
    @include('admin.desain.partials.produk-autocomplete-script')
@endsection

@push('after-scripts')
    @include('admin.desain.partials.copy-link-script')
@endpush
