@extends('layouts.app')

@section('title', 'Referensi HPP')

@section('content')
    <div class="card">
        <div class="card-header">
            <h5 class="mb-1">Referensi HPP {{ $jenis === 'bahan' ? 'Bahan Lanyard' : 'ID Card' }}</h5>
            <small class="text-muted">Biaya dihitung sebelum dibulatkan untuk tampilan rupiah.</small>
        </div>
        <div class="card-body">
            @include('layouts.includes.messages')
            @if ($errors->any())
                <div class="alert alert-danger">{{ $errors->first() }}</div>
            @endif
            <div class="table-responsive hpp-table-wrap">
                <table class="table table-bordered table-sm align-middle">
                    <thead class="table-light">
                        <tr>
                            <th style="min-width:190px">{{ $jenis === 'bahan' ? 'Ukuran / varian' : 'Jenis kartu' }}</th>
                            @foreach (\App\Http\Controllers\Admin\HppReferensiController::KOLOM[$jenis] as $label)
                                <th style="min-width:105px">{{ $label }}</th>
                            @endforeach
                            @if ($jenis === 'bahan')
                                <th>Modal 1S</th><th>Modal 2S</th>
                            @else
                                <th>Modal Kartu</th>
                            @endif
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($data as $row)
                            @php
                                $biaya = json_decode($row->biaya, true);
                                $total = array_sum($biaya);
                            @endphp
                            <tr>
                                <td>
                                    <form id="hpp-{{ $row->id }}" method="POST" action="{{ route('hpp.update', $row->id) }}">@csrf @method('PATCH')</form>
                                    <input class="form-control form-control-sm" form="hpp-{{ $row->id }}" name="nama" aria-label="Nama varian" value="{{ $row->nama }}" required>
                                </td>
                                @foreach (\App\Http\Controllers\Admin\HppReferensiController::KOLOM[$jenis] as $key => $label)
                                    <td><input class="form-control form-control-sm hpp-input" type="number" min="0" max="999999999" step="any" form="hpp-{{ $row->id }}" name="biaya[{{ $key }}]" aria-label="{{ $label }} {{ $row->nama }}" value="{{ $biaya[$key] ?? 0 }}" required></td>
                                @endforeach
                                @if ($jenis === 'bahan')
                                    <td class="fw-bold hpp-total-1">{{ number_format($total - $biaya['print2'], 0, ',', '.') }}</td>
                                    <td class="fw-bold hpp-total-2">{{ number_format($total, 0, ',', '.') }}</td>
                                @else
                                    <td class="fw-bold hpp-total-1">{{ number_format($total, 0, ',', '.') }}</td>
                                @endif
                                <td><button type="submit" form="hpp-{{ $row->id }}" class="btn btn-primary btn-sm">Simpan</button></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if ($jenis === 'bahan')
                <small class="text-muted">Modal 1S = seluruh biaya kecuali Print 2S (tambahan); Modal 2S = Modal 1S + Print 2S.</small>
                <h6 class="mt-4">Perhitungan Tali per Satuan</h6>
                <p class="text-muted mb-2">Satuan = Modal ÷ Bagi. Ketik nama atau SKU untuk memilih produk Inventory. Saat disimpan, nilai Satuan pada produk yang terhubung menjadi harga beli yang ditampilkan di Inventory tanpa mengubah riwayat pembelian atau HPP produk.</p>
                <div class="table-responsive">
                    <table class="table table-bordered table-sm align-middle" style="max-width:850px">
                        <thead class="table-light"><tr><th>Nama Produk</th><th>Modal</th><th>Bagi</th><th>Satuan</th><th>Aksi</th></tr></thead>
                        <tbody>
                             @foreach ($tali as $item)
                                 @php $rincian = json_decode($item->biaya, true); $produkTerpilih = $produkTali->get($item->produk_id_tali); @endphp
                                 <tr class="hpp-tali-row">
                                     <td><form id="tali-{{ $item->id }}" method="POST" action="{{ route('hpp.update', $item->id) }}">@csrf @method('PATCH')</form>
                                         <div class="position-relative hpp-produk-lookup">
                                             <input class="form-control form-control-sm hpp-produk-nama" form="tali-{{ $item->id }}" name="nama" value="{{ $item->nama }}" aria-label="Nama produk" autocomplete="off" required>
                                             <input type="hidden" form="tali-{{ $item->id }}" name="produk_id_tali" value="{{ $produkTerpilih->id ?? '' }}">
                                             <div class="list-group position-absolute w-100 shadow hpp-produk-results" style="z-index:10; max-height:220px; overflow-y:auto" hidden></div>
                                         </div>
                                         <small class="hpp-produk-link">@if ($produkTerpilih)@can('produk_access')<a href="{{ route('produkModel.show', ['produkModel' => $produkTerpilih->model_id, 'kategori_id' => $produkTerpilih->kategori_id]) }}" data-hpp-produk-popup data-sku="{{ $produkTerpilih->id }}" data-kategori="{{ $produkTerpilih->kategori }}" data-model="{{ $produkTerpilih->model }}" data-varian="{{ $produkTerpilih->varian }}" data-harga-beli="{{ $produkTerpilih->harga_beli_hpp }}" data-harga-jual="{{ $produkTerpilih->harga_jual }}" data-hpp="{{ $produkTerpilih->hpp }}" data-satuan="{{ $produkTerpilih->satuan }}">Inventory #{{ $produkTerpilih->id }} — {{ $produkTerpilih->kategori }} / {{ $produkTerpilih->model }} {{ $produkTerpilih->varian }}</a>@else Inventory #{{ $produkTerpilih->id }} — {{ $produkTerpilih->kategori }} / {{ $produkTerpilih->model }} {{ $produkTerpilih->varian }} @endcan @endif</small></td>
                                    <td><input class="form-control form-control-sm hpp-modal" form="tali-{{ $item->id }}" name="biaya[modal]" type="number" min="0" max="999999999" step="any" value="{{ $rincian['modal'] }}" required></td>
                                    <td><input class="form-control form-control-sm hpp-bagi" form="tali-{{ $item->id }}" name="biaya[bagi]" type="number" min="0.00000001" max="999999999" step="any" value="{{ $rincian['bagi'] }}" required></td>
                                    <td class="fw-bold hpp-satuan">{{ number_format($rincian['modal'] / $rincian['bagi'], 0, ',', '.') }}</td>
                                    <td><button type="submit" form="tali-{{ $item->id }}" class="btn btn-primary btn-sm">Simpan</button></td>
                                </tr>
                            @endforeach
                             <tr class="hpp-tali-row">
                                 <td><form id="tali-baru" method="POST" action="{{ route('hpp.tali.store') }}">@csrf</form>
                                     <div class="position-relative hpp-produk-lookup">
                                         <input class="form-control form-control-sm hpp-produk-nama" form="tali-baru" name="nama" placeholder="Cari atau tulis nama produk" aria-label="Nama produk baru" autocomplete="off" required>
                                         <input type="hidden" form="tali-baru" name="produk_id_tali">
                                         <div class="list-group position-absolute w-100 shadow hpp-produk-results" style="z-index:10; max-height:220px; overflow-y:auto" hidden></div>
                                     </div><small class="hpp-produk-link"></small></td>
                                <td><input class="form-control form-control-sm hpp-modal" form="tali-baru" name="modal" type="number" min="0" max="999999999" step="any" aria-label="Modal baru" required></td>
                                <td><input class="form-control form-control-sm hpp-bagi" form="tali-baru" name="bagi" type="number" min="0.00000001" max="999999999" step="any" value="45" aria-label="Bagi baru" required></td>
                                <td class="fw-bold hpp-satuan">0</td>
                                <td><button type="submit" form="tali-baru" class="btn btn-success btn-sm">Tambah</button></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                @can('produk_access')
                    <div class="modal fade" id="hppProdukPopup" tabindex="-1" aria-labelledby="hppProdukPopupTitle" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="hppProdukPopupTitle">Detail Produk Inventory</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                                </div>
                                <div class="modal-body">
                                    <dl class="row mb-0">
                                        <dt class="col-5">SKU</dt><dd class="col-7" data-popup-field="sku"></dd>
                                        <dt class="col-5">Kategori</dt><dd class="col-7" data-popup-field="kategori"></dd>
                                        <dt class="col-5">Produk</dt><dd class="col-7" data-popup-field="model"></dd>
                                        <dt class="col-5">Varian</dt><dd class="col-7" data-popup-field="varian"></dd>
                                        <dt class="col-5">Satuan</dt><dd class="col-7" data-popup-field="satuan"></dd>
                                        <dt class="col-5">Harga beli HPP</dt><dd class="col-7" data-popup-field="hargaBeli"></dd>
                                        <dt class="col-5">Harga jual</dt><dd class="col-7" data-popup-field="hargaJual"></dd>
                                        <dt class="col-5">HPP produk</dt><dd class="col-7" data-popup-field="hpp"></dd>
                                    </dl>
                                </div>
                                <div class="modal-footer">
                                    <a class="btn btn-primary" data-popup-detail href="#">Buka detail lengkap</a>
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                                </div>
                            </div>
                        </div>
                    </div>
                @endcan
            @else
                <h6 class="mt-4">Perhitungan Bahan ID Card per Satuan</h6>
                <p class="text-muted mb-2">Satuan = Harga Bahan Baku ÷ Bagi. Baris tanpa Bagi belum memiliki harga satuan; perhitungan ini tidak otomatis mengubah Modal Kartu di atas.</p>
                <div class="table-responsive">
                    <table class="table table-bordered table-sm align-middle" style="max-width:850px">
                        <thead class="table-light"><tr><th>Nama Barang</th><th>Harga Bahan Baku</th><th>Bagi</th><th>Satuan</th><th>Aksi</th></tr></thead>
                        <tbody>
                            @foreach ($idcardSatuan as $item)
                                @php $rincian = json_decode($item->biaya, true); @endphp
                                <tr class="hpp-tali-row">
                                    <td><form id="satuan-{{ $item->id }}" method="POST" action="{{ route('hpp.update', $item->id) }}">@csrf @method('PATCH')</form>
                                        <input class="form-control form-control-sm" form="satuan-{{ $item->id }}" name="nama" value="{{ $item->nama }}" aria-label="Nama barang" required></td>
                                    <td><input class="form-control form-control-sm hpp-modal" form="satuan-{{ $item->id }}" name="biaya[modal]" type="number" min="0" max="999999999" step="any" value="{{ $rincian['modal'] }}" aria-label="Harga bahan baku {{ $item->nama }}"></td>
                                    <td><input class="form-control form-control-sm hpp-bagi" form="satuan-{{ $item->id }}" name="biaya[bagi]" type="number" min="0.00000001" max="999999999" step="any" value="{{ $rincian['bagi'] }}" aria-label="Bagi {{ $item->nama }}"></td>
                                    <td class="fw-bold hpp-satuan">{{ $rincian['bagi'] ? number_format($rincian['modal'] / $rincian['bagi'], 0, ',', '.') : '—' }}</td>
                                    <td><button type="submit" form="satuan-{{ $item->id }}" class="btn btn-primary btn-sm">Simpan</button></td>
                                </tr>
                            @endforeach
                            <tr class="hpp-tali-row">
                                <td><form id="satuan-baru" method="POST" action="{{ route('hpp.idcard-satuan.store') }}">@csrf</form>
                                    <input class="form-control form-control-sm" form="satuan-baru" name="nama" placeholder="Nama barang baru" aria-label="Nama barang baru" required></td>
                                <td><input class="form-control form-control-sm hpp-modal" form="satuan-baru" name="modal" type="number" min="0" max="999999999" step="any" aria-label="Harga bahan baku baru"></td>
                                <td><input class="form-control form-control-sm hpp-bagi" form="satuan-baru" name="bagi" type="number" min="0.00000001" max="999999999" step="any" aria-label="Bagi baru"></td>
                                <td class="fw-bold hpp-satuan">—</td>
                                <td><button type="submit" form="satuan-baru" class="btn btn-success btn-sm">Tambah</button></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <h6 class="mt-4">Resin A4 (rincian sesuai kolom khusus pada sheet)</h6>
                <div class="table-responsive">
                    <table class="table table-bordered table-sm align-middle">
                        <thead class="table-light"><tr><th>Produk</th>
                            @foreach (\App\Http\Controllers\Admin\HppReferensiController::KOLOM['resin'] as $label)<th>{{ $label }}</th>@endforeach
                            <th>Modal</th><th>Aksi</th>
                        </tr></thead>
                        <tbody>
                            @foreach ($resin as $item)
                                @php $rincian = json_decode($item->biaya, true); @endphp
                                <tr>
                                    <td><form id="hpp-{{ $item->id }}" method="POST" action="{{ route('hpp.update', $item->id) }}">@csrf @method('PATCH')</form>
                                        <input class="form-control form-control-sm" form="hpp-{{ $item->id }}" name="nama" value="{{ $item->nama }}" required></td>
                                    @foreach (\App\Http\Controllers\Admin\HppReferensiController::KOLOM['resin'] as $key => $label)
                                        <td><input class="form-control form-control-sm hpp-input" type="number" step="any" min="0" form="hpp-{{ $item->id }}" name="biaya[{{ $key }}]" aria-label="{{ $label }} {{ $item->nama }}" value="{{ $rincian[$key] }}" required></td>
                                    @endforeach
                                    <td class="fw-bold hpp-total-1">{{ number_format(array_sum($rincian), 0, ',', '.') }}</td>
                                    <td><button type="submit" form="hpp-{{ $item->id }}" class="btn btn-primary btn-sm">Simpan</button></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <h6 class="mt-4">Aksesori (tambahan per kartu, tidak termasuk dalam Modal Kartu)</h6>
                <div class="table-responsive">
                    <table class="table table-bordered table-sm align-middle" style="max-width:650px">
                        <thead class="table-light"><tr><th>Aksesori</th><th>Harga Modal</th><th>Aksi</th></tr></thead>
                        <tbody>
                            @foreach ($aksesori as $item)
                                @php $harga = json_decode($item->biaya, true)['harga']; @endphp
                                <tr>
                                    <td>
                                        <form id="hpp-{{ $item->id }}" method="POST" action="{{ route('hpp.update', $item->id) }}">@csrf @method('PATCH')</form>
                                        <input class="form-control form-control-sm" form="hpp-{{ $item->id }}" name="nama" value="{{ $item->nama }}" aria-label="Nama aksesori" required>
                                    </td>
                                    <td><input class="form-control form-control-sm" type="number" min="0" max="999999999" step="1" form="hpp-{{ $item->id }}" name="biaya[harga]" value="{{ $harga }}" aria-label="Harga {{ $item->nama }}" required></td>
                                    <td><button type="submit" form="hpp-{{ $item->id }}" class="btn btn-primary btn-sm">Simpan</button></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
@endsection

@push('after-scripts')
    <script>
        document.querySelectorAll('.hpp-produk-lookup').forEach(lookup => {
            const input = lookup.querySelector('.hpp-produk-nama');
            const id = lookup.querySelector('[name="produk_id_tali"]');
            const results = lookup.querySelector('.hpp-produk-results');
            const link = lookup.parentElement.querySelector('.hpp-produk-link');
            const inventoryUrl = @json(route('produkModel.show', ['produkModel' => '__MODEL__', 'kategori_id' => '__KATEGORI__']));
            const canViewInventory = @json(auth()->user()->can('produk_access'));
            let timer;
            let request;
            input.addEventListener('input', () => {
                id.value = '';
                link.replaceChildren();
                results.hidden = true;
                clearTimeout(timer);
                if (request) request.abort();
                const term = input.value.trim();
                if (term.length < 2) return;
                timer = setTimeout(async () => {
                    request = new AbortController();
                    try {
                        const response = await fetch(@json(route('hpp.cariProdukTali')) + '?q=' + encodeURIComponent(term), { signal: request.signal });
                        if (!response.ok) return;
                        const products = await response.json();
                        results.replaceChildren();
                        products.forEach(product => {
                            const label = [product.kategori, product.model, product.varian].filter(Boolean).join(' / ');
                            const option = document.createElement('button');
                            option.type = 'button';
                            option.className = 'list-group-item list-group-item-action text-start small';
                            option.textContent = '#' + product.id + ' — ' + label;
                            option.addEventListener('click', () => {
                                input.value = label;
                                id.value = product.id;
                                results.hidden = true;
                                link.replaceChildren();
                                if (canViewInventory) {
                                    const anchor = document.createElement('a');
                                    anchor.href = inventoryUrl.replace('__MODEL__', product.model_id).replace('__KATEGORI__', product.kategori_id);
                                    anchor.dataset.hppProdukPopup = '';
                                    Object.assign(anchor.dataset, {
                                        sku: product.id, kategori: product.kategori, model: product.model,
                                        varian: product.varian || '', satuan: product.satuan || '',
                                        hargaBeli: product.harga_beli_hpp ?? '', hargaJual: product.harga_jual ?? '', hpp: product.hpp ?? ''
                                    });
                                    anchor.textContent = 'Inventory #' + product.id + ' — ' + label;
                                    link.append(anchor);
                                } else {
                                    link.textContent = 'Inventory #' + product.id + ' — ' + label;
                                }
                            });
                            results.append(option);
                        });
                        results.hidden = !products.length;
                    } catch (error) {
                        if (error.name !== 'AbortError') results.hidden = true;
                    }
                }, 250);
            });
            input.addEventListener('blur', () => setTimeout(() => { results.hidden = true; }, 200));
        });
        const produkPopup = document.getElementById('hppProdukPopup');
        if (produkPopup) {
            const modal = new bootstrap.Modal(produkPopup);
            const rupiah = value => value === '' ? '—' : 'Rp ' + new Intl.NumberFormat('id-ID', { maximumFractionDigits: 0 }).format(Number(value));
            document.addEventListener('click', event => {
                const anchor = event.target.closest('[data-hpp-produk-popup]');
                if (!anchor) return;
                event.preventDefault();
                for (const field of ['sku', 'kategori', 'model', 'varian', 'satuan', 'hargaBeli', 'hargaJual', 'hpp']) {
                    produkPopup.querySelector('[data-popup-field="' + field + '"]').textContent =
                        ['hargaBeli', 'hargaJual', 'hpp'].includes(field) ? rupiah(anchor.dataset[field] || '') : (anchor.dataset[field] || '—');
                }
                produkPopup.querySelector('[data-popup-detail]').href = anchor.href;
                modal.show();
            });
        }
        document.querySelectorAll('.hpp-input').forEach(input => input.addEventListener('input', () => {
            const row = input.closest('tr');
            const sum = Array.from(row.querySelectorAll('.hpp-input')).reduce((total, field) => total + (Number(field.value) || 0), 0);
            const format = value => new Intl.NumberFormat('id-ID', { maximumFractionDigits: 0 }).format(value);
            const extra = row.querySelector('[name="biaya[print2]"]');
            row.querySelector('.hpp-total-1').textContent = format(sum - (extra ? Number(extra.value) || 0 : 0));
            if (extra) row.querySelector('.hpp-total-2').textContent = format(sum);
        }));
        document.querySelectorAll('.hpp-tali-row').forEach(row => {
            const modal = row.querySelector('.hpp-modal');
            const bagi = row.querySelector('.hpp-bagi');
            const satuan = row.querySelector('.hpp-satuan');
            const update = () => {
                const hasil = Number(bagi.value) > 0 && modal.value !== '' ? Number(modal.value) / Number(bagi.value) : null;
                satuan.textContent = hasil === null ? '—' : new Intl.NumberFormat('id-ID', { maximumFractionDigits: 0 }).format(hasil);
            };
            modal.addEventListener('input', update);
            bagi.addEventListener('input', update);
        });
    </script>
@endpush
