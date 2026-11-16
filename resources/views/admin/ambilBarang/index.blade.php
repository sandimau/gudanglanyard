@extends('layouts.app')

@section('title', 'Ambil Barang')

@section('content')
    @php
        $kategoriStok = ['Stok cukup', 'Stok kurang', 'Kosong di sistem'];
        $jumlahProduk = $tab === 'arsip'
            ? $arsipCabang->sum(fn ($groups) => $groups->count())
            : $cabangKelompok->sum(fn ($categories) => $categories->sum(fn ($groups) => $groups->count()));
        $jumlahDetail = $tab === 'arsip'
            ? $arsipCabang->sum(fn ($groups) => $groups->sum(fn ($group) => $group['items']->count()))
            : $cabangKelompok->sum(fn ($categories) => $categories->sum(fn ($groups) => $groups->sum(fn ($group) => $group['items']->count())));
    @endphp

    <div class="ambil-barang-page">
        @include('layouts.includes.messages')

        <div class="mb-3">
            <h3 class="mb-1">Ambil Barang</h3>
            <p class="text-muted mb-0">Kebutuhan produk jadi per cabang dan order.</p>
        </div>

        <ul class="nav nav-tabs mb-3" aria-label="Daftar pengambilan barang">
            <li class="nav-item"><a class="nav-link {{ $tab === 'permintaan' ? 'active' : '' }}" href="{{ route('ambilBarang.index', ['tab' => 'permintaan']) }}">Permintaan</a></li>
            <li class="nav-item"><a class="nav-link {{ $tab === 'arsip' ? 'active' : '' }}" href="{{ route('ambilBarang.index', ['tab' => 'arsip']) }}">Arsip</a></li>
        </ul>

        <p class="small text-muted mb-3">{{ $jumlahProduk }} produk · {{ $jumlahDetail }} detail order{{ $tab === 'arsip' ? ' di halaman ini dari ' . number_format($arsipPagination->total(), 0, ',', '.') . ' arsip' : ' menunggu pengambilan' }}. Stok hanya berubah saat pengambilan dikonfirmasi atau opname disimpan.</p>

        @if ($tab === 'permintaan')
            @forelse ($cabangKelompok as $cabangId => $categories)
                @php $cabangNama = $namaCabangAmbilBarang[$cabangId] ?? 'Cabang #' . $cabangId; @endphp
                <div class="card mb-4 ambil-barang-cabang">
                    <div class="card-header fw-semibold">Cabang: {{ $cabangNama }}</div>
                    <div class="card-body p-3">
                        @php $nomor = 0; @endphp
                        @foreach ($kategoriStok as $kategori)
                            @php $groups = $categories->get($kategori, collect()); @endphp
                            @continue($groups->isEmpty())
                            @php $formId = 'ambil-barang-' . $cabangId . '-' . \Illuminate\Support\Str::slug($kategori); @endphp
                            <section class="mb-4 ambil-barang-section" aria-label="{{ $kategori }} di {{ $cabangNama }}">
                                <div class="d-flex align-items-center justify-content-between gap-2 mb-2">
                                    <h5 class="mb-0 {{ $kategori === 'Stok cukup' ? 'text-success' : 'text-danger' }}">
                                        <i class="bx {{ $kategori === 'Stok cukup' ? 'bx-check-circle' : 'bx-error-circle' }}"></i>
                                        {{ $kategori }} <small class="text-muted fw-normal">({{ $groups->count() }} produk)</small>
                                    </h5>
                                    <div class="d-flex flex-wrap gap-2">
                                        @if ($kategori !== 'Kosong di sistem')
                                            <button type="submit" form="{{ $formId }}" class="btn btn-primary btn-sm ambil-barang-submit" disabled
                                                onclick="return confirm('Konfirmasi barang yang dipilih sudah diambil? Stok akan dipotong.')">
                                                <i class="bx bx-package"></i> Pengambilan Barang
                                            </button>
                                        @endif
                                        @can('opname_access')
                                            <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#ambilBarangOpnameModal"
                                                data-cabang-id="{{ $cabangId }}" data-cabang-nama="{{ $cabangNama }}" data-kategori="{{ $kategori }}">
                                                <i class="bx bx-edit"></i> Opname Stok
                                            </button>
                                        @endcan
                                    </div>
                                </div>
                                <form id="{{ $formId }}" action="{{ route('ambilBarang.store') }}" method="post" class="ambil-barang-pilih">
                                    @csrf
                                    <div class="table-responsive border rounded-3">
                                        <table class="table table-hover align-middle mb-0 ambil-barang-table">
                                            <thead>
                                                <tr>
                                                    <th scope="col" class="text-center">Pilih / #</th>
                                                    <th scope="col">Produk</th>
                                                    <th scope="col" class="text-end">Stok</th>
                                                    <th scope="col">No Pesanan</th>
                                                    <th scope="col">Konsumen</th>
                                                    <th scope="col">Jenis</th>
                                                    <th scope="col" class="text-end">Jumlah</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($groups as $group)
                                                    @php
                                                        $nomor++;
                                                        $cekTerakhir = $cekKosong->get($cabangId . ':' . $group['produk_id']);
                                                    @endphp
                                                    @foreach ($group['items'] as $item)
                                                        <tr>
                                                            @if ($loop->first)
                                                                <td rowspan="{{ $group['items']->count() }}" class="text-center text-nowrap align-middle">
                                                                    @if ($kategori !== 'Kosong di sistem')
                                                                        <input class="form-check-input ambil-barang-produk-pilih m-0 me-1" type="checkbox"
                                                                            data-produk-id="{{ $group['produk_id'] }}"
                                                                            aria-label="Pilih semua {{ $group['items']->count() }} detail {{ $group['produk'] }}">
                                                                    @endif
                                                                    {{ $nomor }}
                                                                </td>
                                                                <td rowspan="{{ $group['items']->count() }}" class="ambil-barang-product-cell">
                                                                    <div class="fw-semibold">{{ $group['produk'] }}</div>
                                                                    <div class="d-flex flex-wrap gap-1 mt-2">
                                                                        <button type="submit" form="cek-kosong-{{ $cabangId }}-{{ $group['produk_id'] }}" class="btn btn-outline-secondary btn-sm"
                                                                            onclick="return confirm('Barang sudah dicek secara fisik dan memang kosong? Order tetap belum diambil.')">Fisik kosong</button>
                                                                    </div>
                                                                    @if ($cekTerakhir)
                                                                        <div class="small text-muted mt-2">Dicek kosong: {{ $cekTerakhir->dicek_pada }}</div>
                                                                    @endif
                                                                </td>
                                                                <td rowspan="{{ $group['items']->count() }}" class="text-end fw-semibold {{ $group['stok'] <= 0 ? 'text-danger' : '' }}">{{ number_format($group['stok'], 0, ',', '.') }}</td>
                                                            @endif
                                                            <td class="text-nowrap">
                                                                @if ($kategori !== 'Kosong di sistem')
                                                                    <input class="form-check-input ambil-barang-item m-0 me-2" type="checkbox" name="items[]"
                                                                        value="{{ $item['key'] }}" data-produk-id="{{ $group['produk_id'] }}"
                                                                        data-produk-nama="{{ $group['produk'] }}"
                                                                        data-stok="{{ $group['stok'] }}" data-jumlah="{{ $item['jumlah'] }}"
                                                                        aria-label="Pilih nota {{ $item['nota'] }} sejumlah {{ $item['jumlah'] }}">
                                                                @endif
                                                                <a href="{{ $item['url'] }}" class="popup text-decoration-none fw-semibold">{{ $item['nota'] ?: '#' . $item['detail_id'] }}</a>
                                                            </td>
                                                            <td>{{ $item['pembeli'] }}</td>
                                                            <td>{{ $item['jenis'] }}</td>
                                                            <td class="text-end fw-semibold">{{ number_format($item['jumlah'], 0, ',', '.') }}</td>
                                                        </tr>
                                                    @endforeach
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                    @if ($kategori === 'Kosong di sistem')
                                        <div class="small text-muted mt-2">Stok tercatat kosong. Periksa barang fisik dan lakukan opname sebelum pengambilan.</div>
                                    @else
                                        <div class="d-flex flex-wrap align-items-center gap-2 mt-2 small">
                                            <span class="ambil-barang-ringkasan" aria-live="polite">Belum ada order dipilih</span>
                                            <span class="text-danger ambil-barang-peringatan d-none" aria-live="polite"></span>
                                        </div>
                                    @endif
                                </form>
                                @foreach ($groups as $group)
                                    <form id="cek-kosong-{{ $cabangId }}-{{ $group['produk_id'] }}" action="{{ route('ambilBarang.kosong') }}" method="post">
                                        @csrf
                                        <input type="hidden" name="produk_id" value="{{ $group['produk_id'] }}">
                                        <input type="hidden" name="cabang_id" value="{{ $cabangId }}">
                                    </form>
                                @endforeach
                            </section>
                        @endforeach
                    </div>
                </div>
            @empty
                <div class="card"><div class="card-body text-center py-5 text-muted">Tidak ada permintaan barang.</div></div>
            @endforelse
        @else
            @php $nomorArsip = $arsipPagination->firstItem() ? $arsipPagination->firstItem() - 1 : 0; @endphp
            @forelse ($arsipCabang as $cabangId => $groups)
                <div class="card mb-4 ambil-barang-cabang">
                    <div class="card-header fw-semibold">Cabang: {{ $namaCabangAmbilBarang[$cabangId] ?? 'Cabang #' . $cabangId }}</div>
                    <div class="card-body p-3">
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <h5 class="mb-0">Barang sudah diambil</h5>
                            <small class="text-muted">({{ $groups->count() }} produk)</small>
                        </div>
                        <div class="table-responsive border rounded-3">
                            <table class="table table-hover align-middle mb-0 ambil-barang-table">
                                <thead><tr><th scope="col" class="text-center">#</th><th scope="col">Produk</th><th scope="col" class="text-end">Stok kini</th><th scope="col">No Pesanan</th><th scope="col">Konsumen</th><th scope="col">Jenis</th><th scope="col">Tgl Order</th><th scope="col">Diambil</th><th scope="col" class="text-end">Jumlah</th></tr></thead>
                                <tbody>
                                    @foreach ($groups as $group)
                                        @foreach ($group['items'] as $item)
                                            @php $nomorArsip++; @endphp
                                            <tr>
                                                <td class="text-center">{{ $nomorArsip }}</td>
                                                <td class="ambil-barang-product-cell fw-semibold">{{ $group['produk'] }}</td>
                                                <td class="text-end">{{ number_format($group['stok'], 0, ',', '.') }}</td>
                                                <td>
                                                    @if ($item['url'])
                                                        <a href="{{ $item['url'] }}" class="text-decoration-none fw-semibold">{{ $item['nota'] }}</a>
                                                    @else
                                                        {{ $item['nota'] }}
                                                    @endif
                                                </td>
                                                <td>{{ $item['pembeli'] }}</td>
                                                <td>{{ $item['jenis'] }}</td>
                                                <td class="text-nowrap">{{ $item['tanggal_order'] ? \Carbon\Carbon::parse($item['tanggal_order'])->format('d-m-Y') : '-' }}</td>
                                                <td class="text-nowrap">
                                                    {{ $item['diambil_pada'] ? \Carbon\Carbon::parse($item['diambil_pada'])->format('d-m-Y H:i') : '-' }}
                                                    @if ($item['penandaan_migrasi'])
                                                        <div class="small text-muted">Penandaan migrasi</div>
                                                    @endif
                                                </td>
                                                <td class="text-end fw-semibold">{{ number_format($item['jumlah'], 0, ',', '.') }}</td>
                                            </tr>
                                        @endforeach
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @empty
                <div class="card"><div class="card-body text-center py-5 text-muted">{{ $arsipPagination->total() ? 'Tidak ada arsip pada halaman ini.' : 'Belum ada arsip pengambilan.' }}</div></div>
            @endforelse
            @if ($arsipPagination->hasPages())
                <div class="mt-3">{{ $arsipPagination->links() }}</div>
            @endif
        @endif
    </div>

    @if ($tab === 'permintaan' && $jumlahProduk > 0)
    @can('opname_access')
        <div class="modal fade" id="ambilBarangOpnameModal" tabindex="-1" aria-labelledby="ambilBarangOpnameTitle" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
                <div class="modal-content">
                    <form action="{{ route('ambilBarang.opname') }}" method="post" id="ambilBarangOpnameForm">
                        @csrf
                        <div class="modal-header">
                            <h5 class="modal-title" id="ambilBarangOpnameTitle">Opname Stok</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                        </div>
                        <div class="modal-body">
                            <p class="small text-muted">Pilih satu atau beberapa produk, lalu isi <strong>total stok fisik</strong> yang benar-benar ada di cabang tersebut. Selisih dicatat sebagai opname; order belum dianggap diambil.</p>
                            @if ($errors->has('items'))
                                <div class="alert alert-danger py-2">{{ $errors->first('items') }}</div>
                            @endif
                            <div class="table-responsive border rounded-3 mb-3 ambil-barang-opname-list">
                                <table class="table table-hover align-middle mb-0">
                                    <thead><tr><th scope="col">Pilih</th><th scope="col">Cabang</th><th scope="col">Produk</th><th scope="col" class="text-end">Stok sistem</th><th scope="col" style="min-width: 135px;">Stok fisik</th><th scope="col">Selisih</th></tr></thead>
                                    <tbody>
                                        @php $indexOpname = 0; @endphp
                                        @foreach ($cabangKelompok as $cabangId => $categories)
                                            @foreach ($kategoriStok as $kategori)
                                                @foreach ($categories->get($kategori, collect()) as $group)
                                                    @php
                                                        $opnameDipilih = old('items.' . $indexOpname . '.pilih') == 1;
                                                        $stokInput = old('items.' . $indexOpname . '.stok_fisik');
                                                    @endphp
                                                    <tr class="ambil-barang-opname-row" data-saldo="{{ $group['stok'] }}" data-cabang-id="{{ $cabangId }}" data-kategori="{{ $kategori }}">
                                                        <td>
                                                            <input type="hidden" name="items[{{ $indexOpname }}][produk_id]" value="{{ $group['produk_id'] }}">
                                                            <input type="hidden" name="items[{{ $indexOpname }}][cabang_id]" value="{{ $cabangId }}">
                                                            <input class="form-check-input ambil-barang-opname-check" type="checkbox" name="items[{{ $indexOpname }}][pilih]" value="1"
                                                                @checked($opnameDipilih) aria-label="Pilih opname {{ $group['produk'] }} di {{ $namaCabangAmbilBarang[$cabangId] ?? 'Cabang #' . $cabangId }}">
                                                        </td>
                                                        <td>{{ $namaCabangAmbilBarang[$cabangId] ?? 'Cabang #' . $cabangId }}</td>
                                                        <td class="fw-semibold">{{ $group['produk'] }}</td>
                                                        <td class="text-end">{{ number_format($group['stok'], 0, ',', '.') }}</td>
                                                        <td><input type="number" name="items[{{ $indexOpname }}][stok_fisik]" min="0" step="1" inputmode="numeric"
                                                                value="{{ $stokInput }}" class="form-control form-control-sm ambil-barang-opname-fisik" @disabled(!$opnameDipilih)
                                                                aria-label="Total stok fisik {{ $group['produk'] }}"></td>
                                                        <td class="small ambil-barang-opname-selisih text-nowrap">-</td>
                                                    </tr>
                                                    @php $indexOpname++; @endphp
                                                @endforeach
                                            @endforeach
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            <div class="mb-2 small" id="ambilBarangOpnameRingkasan" aria-live="polite">Pilih produk untuk diopname.</div>
                            <div>
                                <label for="ambilBarangOpnameKeterangan" class="form-label fw-semibold">Alasan selisih stok</label>
                                <textarea id="ambilBarangOpnameKeterangan" name="keterangan" class="form-control @error('keterangan') is-invalid @enderror"
                                    rows="2" maxlength="255" required placeholder="Contoh: hasil hitung fisik gudang">{{ old('keterangan') }}</textarea>
                                @error('keterangan') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-primary" id="ambilBarangOpnameSimpan" disabled
                                onclick="return confirm('Sesuaikan stok semua produk terpilih berdasarkan hasil hitung fisik?')">Simpan Opname</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endcan
    @endif
    @if ($tab === 'permintaan' && $jumlahProduk > 0)
        @include('admin.orders.partials.detail-order-modal')
        @include('admin.projectmps.partials.detail-projectmp-modal')
    @endif
@endsection

@push('after-styles')
    <style>
        @if ($tab === 'permintaan' && $jumlahProduk > 0)
            @include('admin.orders.partials.detail-order-modal-styles')
            @include('admin.projectmps.partials.detail-projectmp-modal-styles')
        @endif
        .ambil-barang-page .ambil-barang-cabang { border-radius: .75rem; }
        .ambil-barang-page .ambil-barang-section:last-child { margin-bottom: 0 !important; }
        .ambil-barang-page .ambil-barang-table { min-width: 980px; font-size: .875rem; }
        .ambil-barang-page .ambil-barang-table th, .ambil-barang-page .ambil-barang-table td { padding: .65rem .6rem; }
        .ambil-barang-page .ambil-barang-table thead th { white-space: nowrap; }
        .ambil-barang-page .ambil-barang-product-cell { min-width: 190px; vertical-align: top; }
        .ambil-barang-page .ambil-barang-item { cursor: pointer; width: 1rem; height: 1rem; }
        .ambil-barang-page .cursor-pointer { cursor: pointer; }
        .ambil-barang-opname-list { max-height: min(55vh, 460px); overflow: auto; }
        .ambil-barang-opname-list thead th { position: sticky; top: 0; z-index: 1; background: var(--bs-body-bg, #fff); }
        .ambil-barang-opname-row[hidden] { display: none; }
    </style>
@endpush

@push('after-scripts')
    <script>
        @if ($tab === 'permintaan' && $jumlahProduk > 0)
            @include('admin.orders.partials.detail-order-modal-js')
            @include('admin.projectmps.partials.detail-projectmp-modal-js')
        @endif

        document.querySelectorAll('.ambil-barang-pilih').forEach(form => {
            const checkboxes = form.querySelectorAll('.ambil-barang-item');
            const productCheckboxes = form.querySelectorAll('.ambil-barang-produk-pilih');
            const summary = form.querySelector('.ambil-barang-ringkasan');
            const warning = form.querySelector('.ambil-barang-peringatan');
            const submit = document.querySelector(`.ambil-barang-submit[form="${form.id}"]`);
            if (!submit) return;

            function updateSelection() {
                productCheckboxes.forEach(productInput => {
                    const items = [...checkboxes].filter(input => input.dataset.produkId === productInput.dataset.produkId);
                    productInput.checked = items.every(input => input.checked);
                    productInput.indeterminate = !productInput.checked && items.some(input => input.checked);
                });
                const selected = [...checkboxes].filter(input => input.checked);
                const quantity = selected.reduce((total, input) => total + Number(input.dataset.jumlah), 0);
                const perProduct = new Map();
                selected.forEach(input => {
                    const id = input.dataset.produkId;
                    const current = perProduct.get(id) ?? { nama: input.dataset.produkNama, jumlah: 0, stok: Number(input.dataset.stok) };
                    current.jumlah += Number(input.dataset.jumlah);
                    perProduct.set(id, current);
                });
                const insufficient = [...perProduct.values()].filter(product => product.jumlah > product.stok);
                summary.textContent = selected.length
                    ? `${selected.length} detail order dipilih · ${quantity} barang`
                    : 'Belum ada order dipilih';
                warning.textContent = insufficient.length
                    ? `Stok tidak cukup untuk ${insufficient.map(product => product.nama).join(', ')}. Pilih lebih sedikit atau lakukan opname.`
                    : '';
                warning.classList.toggle('d-none', !insufficient.length);
                submit.disabled = !selected.length || insufficient.length > 0;
            }

            checkboxes.forEach(input => input.addEventListener('change', updateSelection));
            productCheckboxes.forEach(productInput => productInput.addEventListener('change', () => {
                checkboxes.forEach(input => {
                    if (input.dataset.produkId === productInput.dataset.produkId) input.checked = productInput.checked;
                });
                updateSelection();
            }));
            updateSelection();
        });

        const opnameModal = document.getElementById('ambilBarangOpnameModal');
        if (opnameModal) {
            const form = document.getElementById('ambilBarangOpnameForm');
            const rows = [...form.querySelectorAll('.ambil-barang-opname-row')];
            const submit = document.getElementById('ambilBarangOpnameSimpan');
            const summary = document.getElementById('ambilBarangOpnameRingkasan');
            const title = document.getElementById('ambilBarangOpnameTitle');
            const list = form.querySelector('.ambil-barang-opname-list');

            function updateOpname() {
                let selected = 0;
                let incomplete = 0;
                let changed = 0;
                rows.forEach(row => {
                    const checkbox = row.querySelector('.ambil-barang-opname-check');
                    const input = row.querySelector('.ambil-barang-opname-fisik');
                    const preview = row.querySelector('.ambil-barang-opname-selisih');
                    if (row.hidden) {
                        checkbox.disabled = true;
                        input.disabled = true;
                        return;
                    }
                    checkbox.disabled = false;
                    input.disabled = !checkbox.checked;
                    if (!checkbox.checked) {
                        preview.textContent = '-';
                        return;
                    }
                    selected++;
                    if (input.value === '' || !input.validity.valid) {
                        incomplete++;
                        preview.textContent = 'Isi stok fisik';
                        return;
                    }
                    const difference = Number(input.value) - Number(row.dataset.saldo);
                    if (difference !== 0) changed++;
                    preview.textContent = difference === 0 ? 'Sama' : `${difference > 0 ? '+' : ''}${difference}`;
                });
                summary.textContent = !selected ? 'Pilih produk untuk diopname.'
                    : incomplete ? `${selected} produk dipilih · isi stok fisik yang belum lengkap.`
                    : `${selected} produk dipilih · ${changed} produk memiliki selisih stok.`;
                submit.disabled = !selected || incomplete > 0 || changed === 0;
            }

            rows.forEach(row => {
                row.querySelector('.ambil-barang-opname-check').addEventListener('change', updateOpname);
                row.querySelector('.ambil-barang-opname-fisik').addEventListener('input', updateOpname);
            });
            opnameModal.addEventListener('show.bs.modal', event => {
                if (!opnameModal.dataset.reopen) form.reset();
                const opener = event.relatedTarget;
                const selectedRow = rows.find(row => row.querySelector('.ambil-barang-opname-check').checked);
                const cabangId = opener?.dataset.cabangId ?? selectedRow?.dataset.cabangId ?? rows[0]?.dataset.cabangId;
                const kategori = opener?.dataset.kategori ?? selectedRow?.dataset.kategori ?? rows[0]?.dataset.kategori;
                title.textContent = opener ? `Opname Stok · ${kategori} · ${opener.dataset.cabangNama}` : `Opname Stok · ${kategori}`;
                rows.forEach(row => {
                    row.hidden = row.dataset.cabangId !== cabangId || row.dataset.kategori !== kategori;
                });
                list.scrollTop = 0;
                updateOpname();
            });
            updateOpname();

            @if (is_array(old('items.0')) && $errors->any())
                opnameModal.dataset.reopen = '1';
                bootstrap.Modal.getOrCreateInstance(opnameModal).show();
                delete opnameModal.dataset.reopen;
            @endif
        }
    </script>
@endpush
