@extends('layouts.app')

@section('title')
    Produk Marketplace
@endsection

@section('content')
    <div class="bg-light rounded">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Produk Marketplace</h5>
            </div>
            <div class="card-body">
                @include('layouts.includes.messages')

                {{-- Filter toko & kategori --}}
                <form method="GET" action="{{ route('marketplaces.produk') }}" class="row g-2 mb-3">
                    <div class="col-md-4">
                        <label class="form-label mb-1">Toko Shopee</label>
                        <select name="marketplace_id" class="form-select" onchange="this.form.submit()">
                            @forelse ($tokos as $toko)
                                <option value="{{ $toko->id }}"
                                    {{ $config && $config->id == $toko->id ? 'selected' : '' }}>
                                    {{ $toko->nama }}
                                </option>
                            @empty
                                <option value="">Belum ada toko Shopee</option>
                            @endforelse
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label mb-1">Kategori</label>
                        <select name="kategori_id" class="form-select" onchange="this.form.submit()">
                            <option value="">Semua kategori</option>
                            @foreach ($kategoris as $kategori)
                                <option value="{{ $kategori->id }}"
                                    {{ (string) $kategoriId === (string) $kategori->id ? 'selected' : '' }}>
                                    {{ $kategori->nama }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary">Filter</button>
                        <a href="{{ route('marketplaces.produk', ['marketplace_id' => $config->id ?? '']) }}"
                            class="btn btn-outline-secondary ms-2">Reset</a>
                    </div>
                </form>

                @if (!$config)
                    <div class="alert alert-warning">Belum ada toko Shopee yang dapat ditampilkan.</div>
                @else
                    @if (empty($config->shop_id) || empty($config->access_token))
                        <div class="alert alert-warning">
                            Toko <b>{{ $config->nama }}</b> belum tersinkron dengan Shopee. Update harga ke Shopee
                            belum bisa dilakukan sebelum disinkronkan.
                        </div>
                    @endif

                    {{-- Bulk: stok minimal marketplace --}}
                    <form method="POST" action="{{ route('marketplaces.bulkStokMin') }}" id="bulkStokForm"
                        class="border rounded p-3 mb-3 bg-white">
                        @csrf
                        <label class="form-label mb-1">stok minimal marketplace</label>
                        <div class="d-flex align-items-center gap-2">
                            <input type="number" min="0" name="stok_min_mp" class="form-control" style="max-width: 220px"
                                placeholder="kosongkan = hapus">
                            <button type="submit" class="btn btn-primary">proses terpilih</button>
                            <span class="text-muted">centang baris di bawah untuk diubah massal.</span>
                        </div>
                    </form>

                    @if ($items && $items->hasPages())
                        <div class="mb-3">
                            {{ $items->links() }}
                        </div>
                    @endif

                    <div class="table-responsive">
                        <table class="table table-striped align-middle">
                            <thead>
                                <tr>
                                    <th scope="col" style="width: 36px">
                                        <input type="checkbox" id="checkAll">
                                    </th>
                                    <th scope="col">kategori utama</th>
                                    <th scope="col">kategori</th>
                                    <th scope="col">produk</th>
                                    <th scope="col" class="text-end">hpp</th>
                                    <th scope="col" class="text-end">harga jual</th>
                                    <th scope="col" class="text-end">margin (%)</th>
                                    <th scope="col" class="text-end">stok min</th>
                                    <th scope="col" class="text-center">total varian</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php $utamaPrev = null; $kategoriPrev = null; @endphp
                                @forelse ($items as $row)
                                    @php
                                        $sameUtama = $utamaPrev === $row->kategori_utama_id;
                                        $sameKategori = $sameUtama && $kategoriPrev === $row->kategori_id;
                                        $utamaPrev = $row->kategori_utama_id;
                                        $kategoriPrev = $row->kategori_id;
                                    @endphp
                                    <tr>
                                        <td>
                                            <input type="checkbox" class="rowCheck" value="{{ $row->produk_model_id }}">
                                        </td>
                                        <td>{{ $sameUtama ? '' : $row->namaUtama }}</td>
                                        <td>{{ $sameKategori ? '' : $row->kategori }}</td>
                                        <td>{{ $row->namaProduk }}</td>
                                        <td class="text-end">{{ number_format((float) $row->hpp, 0, ',', '.') }}</td>
                                        <td class="text-end">
                                            <a href="#" class="text-primary fw-bold text-decoration-none"
                                                data-bs-toggle="modal" data-bs-target="#hargaModal{{ $row->produk_model_id }}">
                                                {{ number_format((float) $row->harga, 0, ',', '.') }}
                                            </a>
                                        </td>
                                        <td class="text-end" style="min-width: 130px">
                                            <form method="POST" action="{{ route('marketplaces.updateMargin') }}"
                                                class="d-flex justify-content-end align-items-center gap-1">
                                                @csrf
                                                <input type="hidden" name="produk_model_id" value="{{ $row->produk_model_id }}">
                                                <input type="number" step="1" name="margin"
                                                    value="{{ $row->margin }}" class="form-control form-control-sm text-end"
                                                    style="max-width: 80px"
                                                    {{ (float) $row->hpp <= 0 ? 'disabled' : '' }}>
                                                <button type="submit" class="btn btn-sm btn-outline-success"
                                                    title="Simpan margin"
                                                    {{ (float) $row->hpp <= 0 ? 'disabled' : '' }}>&check;</button>
                                            </form>
                                        </td>
                                        <td class="text-end">{{ $row->stok_min_mp ?? '-' }}</td>
                                        <td class="text-center">
                                            <a href="#" data-bs-toggle="modal"
                                                data-bs-target="#varianModal{{ $row->produk_model_id }}"
                                                class="badge bg-warning text-dark text-decoration-none">
                                                {{ $row->total_varian }}
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="9" class="text-center text-muted">
                                            Tidak ada produk yang cocok dengan filter ini.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    {{-- Modal: edit harga jual + daftar varian per produk model --}}
                    @foreach ($items as $row)
                        {{-- Modal harga jual --}}
                        <div class="modal fade" id="hargaModal{{ $row->produk_model_id }}" tabindex="-1"
                            aria-hidden="true">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <form method="POST" action="{{ route('marketplaces.updateHargaModel') }}">
                                        @csrf
                                        <input type="hidden" name="produk_model_id" value="{{ $row->produk_model_id }}">
                                        <div class="modal-header">
                                            <h5 class="modal-title">Edit Harga Jual</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"
                                                aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body">
                                            <p class="mb-2"><b>{{ $row->namaProduk }}</b></p>
                                            <label class="form-label mb-1">Harga jual (produk)</label>
                                            <input type="number" min="0" name="harga" class="form-control"
                                                value="{{ (int) $row->harga }}">
                                            <small class="text-muted">HPP: {{ number_format((float) $row->hpp, 0, ',', '.') }}</small>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                            <button type="submit" class="btn btn-primary">Simpan</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>

                        {{-- Modal daftar varian --}}
                        <div class="modal fade" id="varianModal{{ $row->produk_model_id }}" tabindex="-1"
                            aria-hidden="true">
                            <div class="modal-dialog modal-xl modal-dialog-scrollable">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title">Varian - {{ $row->namaProduk }}</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"
                                            aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="table-responsive">
                                            <table class="table table-sm table-striped align-middle">
                                                <thead>
                                                    <tr>
                                                        <th>Produk (listing)</th>
                                                        <th>Varian</th>
                                                        <th class="text-end" style="min-width: 140px">Harga Jual</th>
                                                        <th class="text-end">Harga di Shopee</th>
                                                        <th class="text-end">Harga Baru</th>
                                                        <th>Update Terakhir</th>
                                                        <th>Aksi</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach ($varianPerModel[$row->produk_model_id] ?? [] as $v)
                                                        <tr data-paket="{{ max((int) $v->paket, 1) }}"
                                                            data-markup="{{ (int) $config->harga }}">
                                                            <td>{{ $v->nama }}</td>
                                                            <td>{{ $v->varian }}</td>
                                                            <td class="text-end">
                                                                <input type="number" min="0"
                                                                    class="form-control form-control-sm text-end input-harga-jual"
                                                                    value="{{ (int) $v->harga_jual }}"
                                                                    data-pm-id="{{ $v->pm_id }}">
                                                            </td>
                                                            <td class="text-end">
                                                                {{ number_format((int) $v->harga_mp, 0, ',', '.') }}</td>
                                                            <td class="text-end cell-harga-baru">
                                                                @if ($v->harga_baru <= 0)
                                                                    <span class="text-danger">harga kosong</span>
                                                                @elseif ($v->berubah)
                                                                    <span class="text-success fw-bold harga-baru-val">
                                                                        {{ number_format($v->harga_baru, 0, ',', '.') }}</span>
                                                                @else
                                                                    <span class="text-muted harga-baru-val">
                                                                        {{ number_format($v->harga_baru, 0, ',', '.') }}</span>
                                                                @endif
                                                            </td>
                                                            <td>{{ $v->update_harga_terakhir ?? '-' }}</td>
                                                            <td class="text-nowrap">
                                                                <form method="POST"
                                                                    action="{{ route('marketplaces.updateHargaVarian') }}"
                                                                    class="d-inline form-simpan-harga">
                                                                    @csrf
                                                                    <input type="hidden" name="pm_id" value="{{ $v->pm_id }}">
                                                                    <input type="hidden" name="harga" class="hidden-harga"
                                                                        value="{{ (int) $v->harga_jual }}">
                                                                    <button type="submit" class="btn btn-sm btn-outline-primary"
                                                                        title="Simpan harga jual">Simpan</button>
                                                                </form>
                                                                <form method="POST"
                                                                    action="{{ route('marketplaces.updateHarga', $config->id) }}"
                                                                    class="d-inline form-update-shopee"
                                                                    onsubmit="return confirmUpdateShopee(this);">
                                                                    @csrf
                                                                    <input type="hidden" name="pm_id" value="{{ $v->pm_id }}">
                                                                    <input type="hidden" name="harga" class="hidden-harga"
                                                                        value="{{ (int) $v->harga_jual }}">
                                                                    <button type="submit"
                                                                        class="btn btn-sm {{ $v->berubah ? 'btn-success' : 'btn-outline-secondary' }}"
                                                                        {{ empty($config->shop_id) || empty($config->access_token) ? 'disabled' : '' }}>
                                                                        Update ke Shopee
                                                                    </button>
                                                                </form>
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                @endif
            </div>
        </div>
    </div>
@endsection

@push('after-scripts')
    <script>
        // Pilih semua baris
        const checkAll = document.getElementById('checkAll');
        if (checkAll) {
            checkAll.addEventListener('change', function () {
                document.querySelectorAll('.rowCheck').forEach(cb => cb.checked = this.checked);
            });
        }

        // Submit bulk stok min: kumpulkan baris tercentang jadi ids[]
        const bulkForm = document.getElementById('bulkStokForm');
        if (bulkForm) {
            bulkForm.addEventListener('submit', function (e) {
                this.querySelectorAll('input[data-bulk-id]').forEach(el => el.remove());
                const checked = document.querySelectorAll('.rowCheck:checked');
                if (checked.length === 0) {
                    e.preventDefault();
                    alert('Pilih minimal satu produk dengan mencentang baris.');
                    return;
                }
                checked.forEach(cb => {
                    const i = document.createElement('input');
                    i.type = 'hidden';
                    i.name = 'ids[]';
                    i.value = cb.value;
                    i.setAttribute('data-bulk-id', '1');
                    this.appendChild(i);
                });
            });
        }

        // Popup varian: sync input harga jual ke hidden field form + hitung ulang harga baru
        function formatRp(n) {
            return new Intl.NumberFormat('id-ID').format(n);
        }

        function hitungHargaBaru(harga, paket, markup) {
            if (!harga || harga <= 0) return 0;
            return Math.floor(harga * paket * (100 + markup) / 100);
        }

        function refreshBarisVarian(tr) {
            const harga = parseInt(tr.querySelector('.input-harga-jual')?.value || '0', 10);
            const paket = parseInt(tr.dataset.paket || '1', 10);
            const markup = parseInt(tr.dataset.markup || '0', 10);
            const hargaBaru = hitungHargaBaru(harga, paket, markup);
            const cell = tr.querySelector('.cell-harga-baru');

            tr.querySelectorAll('.hidden-harga').forEach(el => el.value = harga);

            if (!cell) return;

            if (hargaBaru <= 0) {
                cell.innerHTML = '<span class="text-danger">harga kosong</span>';
            } else {
                cell.innerHTML = '<span class="text-success fw-bold harga-baru-val">' + formatRp(hargaBaru) + '</span>';
            }
        }

        document.querySelectorAll('.input-harga-jual').forEach(input => {
            input.addEventListener('input', function () {
                refreshBarisVarian(this.closest('tr'));
            });
        });

        document.querySelectorAll('.form-simpan-harga, .form-update-shopee').forEach(form => {
            form.addEventListener('submit', function () {
                refreshBarisVarian(this.closest('tr'));
            });
        });

        window.confirmUpdateShopee = function (form) {
            const tr = form.closest('tr');
            refreshBarisVarian(tr);
            const harga = parseInt(tr.querySelector('.input-harga-jual')?.value || '0', 10);
            const paket = parseInt(tr.dataset.paket || '1', 10);
            const markup = parseInt(tr.dataset.markup || '0', 10);
            const hargaBaru = hitungHargaBaru(harga, paket, markup);
            if (hargaBaru <= 0) {
                alert('Harga jual masih kosong/0.');
                return false;
            }
            return confirm('Kirim harga ' + formatRp(hargaBaru) + ' ke Shopee untuk varian ini?');
        };
    </script>
@endpush
