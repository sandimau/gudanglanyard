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
@if ($detail->produk?->produkModel?->kategori?->kategoriUtama?->is_custom && $detail->bahanCustom()->exists())
    @if ($detail->bahanCustom()->whereNull('ambil_barang_id')->exists())
        <a href="{{ route('ambilBarang.index') }}" class="badge bg-warning text-dark text-decoration-none">Belum ambil bahan</a>
    @else
        <span class="badge bg-success">Bahan sudah diambil</span>
    @endif
@endif
