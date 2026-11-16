@extends('layouts.app')

@section('title')
    Buku Besar | {{ $akunDetail->nama }}
@endsection

@section('content')
    <div class="bg-light rounded">
        @include('layouts.includes.messages')
        <div class="card">
            <div class="card-header">
                <div class="row">
                    <div class="col-lg-6">
                        <h5 class="card-title"><a href="{{ route('akunDetails.index') }}">{{ $akunDetail->nama }}</a> >
                            Buku Besar</h5>
                    </div>
                    <div style="text-align: right" class="col-lg-6">
                        @can('akun_kategori_create')
                            @if ($akunDetail->akun_kategori_id == 1)
                                <a href="{{ route('akundetail.transferLain', $akunDetail->id) }}"
                                    class="btn btn-success text-white">pemasukan lain</a>
                                <a href="{{ route('akundetail.transfer', $akunDetail->id) }}"
                                    class="btn btn-primary">transfer</a>
                            @endif
                        @endcan
                    </div>
                </div>
            </div>
            <div class="card-body">
                @can('akun_kategori_create')
                    @if ((int) $akunDetail->akun_kategori_id === 5 && $bukubesars->total() === 0 && (float) $akunDetail->saldo === 0.0)
                        <form method="POST" action="{{ route('akundetail.modalAwal.store', $akunDetail->id) }}" class="mb-4">
                            @csrf
                            <label for="jumlah" class="form-label required">Nominal Modal Awal (Rp)</label>
                            <div class="input-group">
                                <input type="number" name="jumlah" id="jumlah" min="1" max="2147483647" step="1"
                                    value="{{ old('jumlah') }}" required
                                    class="form-control {{ $errors->has('jumlah') ? 'is-invalid' : '' }}">
                                <button type="submit" class="btn btn-primary">Simpan Modal Awal</button>
                            </div>
                            @error('jumlah')
                                <div class="text-danger mt-1">{{ $message }}</div>
                            @enderror
                        </form>
                    @endif
                @endcan
                {{ $bukubesars->links() }}
                <div class="table-responsive mt-3">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th scope="col">tanggal</th>
                                <th scope="col">ket</th>
                                <th scope="col">kredit</th>
                                <th scope="col">debet</th>
                                <th scope="col">saldo</th>
                                <th scope="col">user</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($bukubesars as $bukubesar)
                                <tr>
                                    <td>{{ $bukubesar->created_at }}</td>
                                    <td>
                                        @if ($bukubesar->detail_id)
                                            @if ($bukubesar->kode == 'blj')
                                                <a class="popup" href="{{ route('belanja.detail', $bukubesar->detail_id) }}">{{ $bukubesar->ket }}</a>
                                            @elseif ($bukubesar->kode == 'byr')
                                                <a class="popup" href="{{ route('order.detail', $bukubesar->detail_id) }}">{{ $bukubesar->ket }}</a>
                                            @else
                                                {{ $bukubesar->ket }}
                                            @endif
                                        @else
                                            {{ $bukubesar->ket }}
                                        @endif
                                    </td>
                                    <td>{{ number_format($bukubesar->kredit, 0, ',', '.') }}</td>
                                    <td>{{ number_format($bukubesar->debet, 0, ',', '.') }}</td>
                                    <td>{{ number_format($bukubesar->saldo, 0, ',', '.') }}</td>
                                    <td>{{ $bukubesar->user->name ?? '-' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    @include('admin.orders.partials.detail-order-modal')
@endsection

@push('after-scripts')
    <script>
        @include('admin.orders.partials.detail-order-modal-js')
    </script>
    <style>
        @include('admin.orders.partials.detail-order-modal-styles')
    </style>
@endpush
