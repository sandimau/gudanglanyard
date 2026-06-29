@extends('layouts.app')

@section('title')
    Produk Kategori Utama
@endsection

@section('content')
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Kategori Utama</h5>
            <a href="{{ route('produk-kategori-utama.create') }}" class="btn btn-primary">Tambah Kategori</a>
        </div>

        <div class="card-body">
            @if (session('success'))
                <div class="alert alert-success">
                    {{ session('success') }}
                </div>
            @endif

            {{ $kategoriUtamas->links() }}
            <table class="table">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Nama</th>
                        <th>Jenis</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($kategoriUtamas as $index => $kategori)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td><a href="{{ route('produk-kategori.index', $kategori->id) }}">{{ $kategori->nama }}</a></td>
                            <td>
                                <ul class="list-unstyled mb-0">
                                    @if ($kategori->jual)
                                        <li>✓ Jual</li>
                                    @endif
                                    @if ($kategori->beli)
                                        <li>✓ Beli</li>
                                    @endif
                                    @if ($kategori->stok)
                                        <li>✓ Stok</li>
                                    @endif
                                    @if ($kategori->produksi)
                                        <li>✓ Produksi</li>
                                    @endif
                                </ul>
                            </td>
                            <td>
                                <a href="{{ route('produk-kategori-utama.edit', $kategori) }}"
                                    class="btn btn-sm btn-warning">Edit</a>
                                {{-- <form action="{{ route('produk-kategori-utama.destroy', $kategori) }}" method="POST" class="d-inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Yakin ingin menghapus?')">Hapus</button>
                        </form> --}}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

        </div>
    </div>
@endsection
