@if ((int) ($detail->produk?->produkModel?->stok ?? 0) === 1)
    @if ($detail->pengambilan)
        <span class="badge bg-success">Barang sudah diambil</span>
    @elseif ($detail->produksi?->nama !== 'batal')
        @if (auth()->user()?->can('produk_stok_access') || auth()->user()?->can('produk_access'))
            <a href="{{ route('ambilBarang.index') }}" class="badge bg-warning text-dark text-decoration-none">Belum ambil barang</a>
        @else
            <span class="badge bg-warning text-dark">Belum ambil barang</span>
        @endif
    @endif
@endif
