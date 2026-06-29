@extends('layouts.app')

@section('title')
    Produk Produksi
@endsection

@section('content')
    <div class="bg-light rounded">
        <div class="card">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="card-title">Produk Produksi</h5>
                    </div>
                    <a href="{{ route('produkProduksi.create') }}" class="btn btn-primary">Tambah Produk Produksi</a>
                </div>
            </div>
            <div class="card-body">
                @include('layouts.includes.messages')
                <div class="table-responsive">
                    <table class="table table-striped" id="myTable">
                        <thead>
                            <tr>
                                <th scope="col">#</th>
                                <th scope="col">Produk</th>
                                <th scope="col">Satuan</th>
                                <th scope="col">Panjang</th>
                                <th scope="col">Lebar</th>
                                <th scope="col">Perbandingan</th>
                                <th scope="col">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($produkProduksis as $produkProduksi)
                                <tr>
                                    <td>{{ $produkProduksi->id }}</td>
                                    <td>
                                        @if($produkProduksi->produk)
                                            {{ $produkProduksi->produk->nama_lengkap ?? '-' }}
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge bg-info">{{ ucfirst($produkProduksi->satuan) }}</span>
                                    </td>
                                    <td>{{ $produkProduksi->panjang ? number_format($produkProduksi->panjang, 2) : '-' }}</td>
                                    <td>{{ $produkProduksi->lebar ? number_format($produkProduksi->lebar, 2) : '-' }}</td>
                                    <td>{{ $produkProduksi->perbandingan ? number_format($produkProduksi->perbandingan, 2) : '-' }}</td>
                                    <td>
                                        <div class="d-flex gap-1">
                                            <button type="button" class="btn btn-sm btn-danger"
                                                onclick="hapusData({{ $produkProduksi->id }}, '{{ route('produkProduksi.destroy', $produkProduksi->id) }}')">
                                                <i class='bx bx-trash'></i> Hapus
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                            @endforelse
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

        function hapusData(id, url) {
            if (confirm('Yakin ingin menghapus data ini?')) {
                // Create form untuk delete
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = url;

                // Add CSRF token
                const csrfInput = document.createElement('input');
                csrfInput.type = 'hidden';
                csrfInput.name = '_token';
                csrfInput.value = '{{ csrf_token() }}';
                form.appendChild(csrfInput);

                // Add method spoofing
                const methodInput = document.createElement('input');
                methodInput.type = 'hidden';
                methodInput.name = '_method';
                methodInput.value = 'DELETE';
                form.appendChild(methodInput);

                document.body.appendChild(form);
                form.submit();
            }
        }
    </script>
@endpush

