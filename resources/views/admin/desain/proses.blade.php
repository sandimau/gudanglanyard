@extends('layouts.app')

@section('title', 'Proses Desain')

@section('content')
    @php
        $belumAdaTab = $perTab->get('', collect());
    @endphp
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
        <div>
            <h4 class="mb-1">Proses Desain</h4>
            <p class="text-muted mb-0">Pantau dan pindahkan desain di setiap tahap proses.</p>
        </div>
        <div class="d-flex flex-wrap align-items-center gap-2">
            <input type="search" id="cariDesain" class="form-control desain-search" placeholder="Cari konsumen, produk, tema, tipe, atau keterangan..." aria-label="Cari konsumen, produk, tema, tipe, atau keterangan">
            <select id="filterTipeDesain" class="form-select desain-filter-tipe" aria-label="Filter tipe desain">
                <option value="">Semua Tipe</option>
                <option value="offline">Offline</option>
                <option value="online">Online</option>
            </select>
            <a href="{{ route('desain.index') }}" class="btn btn-outline-primary text-nowrap">
                <i class="bx bx-list-ul"></i> Daftar Desain
            </a>
            @if ($allTabs->isNotEmpty())
                <button type="button" class="btn btn-primary text-nowrap" data-bs-toggle="modal"
                    data-bs-target="#tambahDesainModal">+ Desain</button>
            @endif
        </div>
    </div>
    @include('layouts.includes.messages')

    @if ($allTabs->isEmpty())
        <div class="alert alert-info">
            @if ($canSetupDesain)
                Belum ada sub kategori proses. <a href="{{ route('desain.setup') }}">Buat kategori dan sub kategori desain</a> sebelum menambahkan desain.
            @else
                Setup desain belum tersedia. Hubungi pengguna yang bisa mengatur kategori desain.
            @endif
        </div>
    @else
        <div class="card mb-3">
            <div class="card-body">
                <ul class="nav nav-tabs desain-tabs flex-nowrap" id="desainGrupTab" role="tablist">
                    @foreach ($tabs as $grup => $items)
                        <li class="nav-item" role="presentation">
                            <button class="nav-link {{ $loop->first ? 'active' : '' }}" id="desain-grup-{{ $loop->index }}-tab"
                                data-bs-toggle="tab" data-bs-target="#desain-grup-{{ $loop->index }}" type="button" role="tab"
                                aria-selected="{{ $loop->first ? 'true' : 'false' }}">
                                {{ $grup }} <span class="badge bg-primary rounded-pill">{{ $items->sum(fn ($tab) => $perTab->get($tab->id, collect())->count()) }}</span>
                            </button>
                        </li>
                    @endforeach
                </ul>
                <div class="tab-content" id="desainGrupContent">
                    @foreach ($tabs as $grup => $items)
                        <div class="tab-pane fade {{ $loop->first ? 'show active' : '' }}" id="desain-grup-{{ $loop->index }}"
                            role="tabpanel" aria-labelledby="desain-grup-{{ $loop->index }}-tab">
                            <ul class="nav nav-tabs desain-tabs flex-nowrap mt-3" role="tablist">
                                @foreach ($items as $tab)
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link {{ $loop->first ? 'active' : '' }}" id="desain-tab-{{ $tab->id }}-button"
                                            data-bs-toggle="tab" data-bs-target="#desain-tab-{{ $tab->id }}-panel" type="button" role="tab"
                                            aria-selected="{{ $loop->first ? 'true' : 'false' }}">
                                            <span class="desain-tab-dot" style="background-color: {{ $tab->warna }}"></span>{{ $tab->nama }}
                                            <span class="badge bg-success rounded-pill">{{ $perTab->get($tab->id, collect())->count() }}</span>
                                        </button>
                                    </li>
                                @endforeach
                            </ul>
                            <div class="tab-content pt-3">
                                @foreach ($items as $tab)
                                    <div class="tab-pane fade {{ $loop->first ? 'show active' : '' }}" id="desain-tab-{{ $tab->id }}-panel"
                                        role="tabpanel" aria-labelledby="desain-tab-{{ $tab->id }}-button">
                                        @include('admin.desain.partials.daftar', ['desainItems' => $perTab->get($tab->id, collect())])
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    @if ($belumAdaTab->isNotEmpty())
        <div class="card">
            <div class="card-header"><h5 class="mb-0">Belum Ada Sub Kategori <span class="badge bg-secondary">{{ $belumAdaTab->count() }}</span></h5></div>
            <div class="card-body">
                @include('admin.desain.partials.daftar', ['desainItems' => $belumAdaTab])
            </div>
        </div>
    @endif
    @if ($allTabs->isNotEmpty())
        @include('admin.desain.partials.tambah-modal', ['modalAsal' => 'proses'])
    @endif
    @include('admin.desain.partials.detail-modal')
    @include('admin.desain.partials.edit-modal', ['modalAsal' => 'proses'])
    @include('admin.desain.partials.produk-autocomplete-script')
@endsection

@push('after-styles')
    <style>
        .desain-tabs { overflow-x: auto; overflow-y: hidden; max-width: 100%; scrollbar-width: thin; }
        .desain-tabs .nav-item { flex-shrink: 0; }
        .desain-tabs .nav-link { white-space: nowrap; }
        .desain-tab-dot { display: inline-block; width: .65rem; height: .65rem; border-radius: 50%; margin-right: .4rem; }
        .desain-search { flex: 1 1 240px; width: auto; max-width: 320px; min-width: 180px; }
        .desain-filter-tipe { flex: 0 0 140px; width: 140px; }
        .desain-table { min-width: 820px; }
        .desain-table thead th { white-space: nowrap; }
        .desain-row[hidden] { display: none; }
        .desain-row__nama { display: block; max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; font-weight: 600; }
        .desain-row__nama:hover { text-decoration: underline; }
        .desain-row__status { width: 180px; }
    </style>
@endpush

@push('after-scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const search = document.getElementById('cariDesain');
            const filterTipe = document.getElementById('filterTipeDesain');
            const savedGroup = sessionStorage.getItem('desainGrupTab');
            const savedTab = sessionStorage.getItem('desainProsesTab');
            document.querySelectorAll('#desainGrupTab [data-bs-toggle="tab"]').forEach(function (button) {
                button.addEventListener('shown.bs.tab', function () { sessionStorage.setItem('desainGrupTab', button.id); });
            });
            document.querySelectorAll('#desainGrupContent .tab-pane .nav-tabs [data-bs-toggle="tab"]').forEach(function (button) {
                button.addEventListener('shown.bs.tab', function () { sessionStorage.setItem('desainProsesTab', button.id); });
            });

            function saveTargetStatus(statusId) {
                const button = document.getElementById('desain-tab-' + statusId + '-button');
                const groupPane = button?.closest('#desainGrupContent > .tab-pane');
                if (!button || !groupPane) return;
                sessionStorage.setItem('desainGrupTab', groupPane.id + '-tab');
                sessionStorage.setItem('desainProsesTab', button.id);
            }

            document.querySelectorAll('[data-desain-status-select]').forEach(function (select) {
                select.addEventListener('change', function () {
                    if (!select.value || select.dataset.submitting) return;
                    select.dataset.submitting = '1';
                    saveTargetStatus(select.value);
                    select.form.requestSubmit();
                });
            });

            function restoreStatusTab() {
                const button = savedTab && document.getElementById(savedTab);
                if (button) bootstrap.Tab.getOrCreateInstance(button).show();
            }
            const groupButton = savedGroup && document.getElementById(savedGroup);
            if (groupButton && !groupButton.classList.contains('active')) {
                groupButton.addEventListener('shown.bs.tab', restoreStatusTab, { once: true });
                bootstrap.Tab.getOrCreateInstance(groupButton).show();
            } else {
                restoreStatusTab();
            }
            function filterDesainRows() {
                const value = search.value.trim().toLocaleLowerCase();
                const tipe = filterTipe.value;
                document.querySelectorAll('.desain-row').forEach(function (row) {
                    const cocokCari = row.dataset.search.includes(value);
                    const cocokTipe = !tipe || row.dataset.tipe === tipe;
                    row.hidden = !(cocokCari && cocokTipe);
                });
            }
            search.addEventListener('input', filterDesainRows);
            filterTipe.addEventListener('change', filterDesainRows);
        });
    </script>
@endpush
