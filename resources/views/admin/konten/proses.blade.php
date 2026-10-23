@extends('layouts.app')

@section('title', 'Proses Konten')

@section('content')
    @php
        $allTabs = $tabs->flatten();
        $belumAdaTab = $perTab->get('', collect());
    @endphp
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
        <div>
            <h4 class="mb-1">Proses Konten</h4>
            <p class="text-muted mb-0">Pantau dan pindahkan konten di setiap tahap proses.</p>
        </div>
        <div class="d-flex flex-wrap align-items-center gap-2">
            <input type="search" id="cariKonten" class="form-control konten-search" placeholder="Cari topik, platform, atau tujuan..." aria-label="Cari topik, platform, atau tujuan">
            <a href="{{ route('konten.semua') }}" class="btn btn-outline-primary text-nowrap">
                <i class="bx bx-list-ul"></i> Semua Konten
            </a>
            @if ($allTabs->isNotEmpty())
                <button type="button" class="btn btn-primary text-nowrap" data-bs-toggle="modal"
                    data-bs-target="#tambahKontenModal" data-tanggal="{{ now()->toDateString() }}">+ Konten</button>
            @endif
        </div>
    </div>
    @include('layouts.includes.messages')

    @if ($allTabs->isEmpty())
        <div class="alert alert-info">
            @if ($canSetupKonten)
                Belum ada tab proses. <a href="{{ route('konten.setup') }}">Buat tab proses konten</a> sebelum menambahkan jadwal.
            @else
                Setup konten belum tersedia. Hubungi super atau manager sebelum menambahkan jadwal.
            @endif
        </div>
    @else
        <div class="card mb-3">
            <div class="card-body">
                <ul class="nav nav-tabs konten-tabs flex-nowrap" id="kontenGrupTab" role="tablist">
                    @foreach ($tabs as $grup => $items)
                        <li class="nav-item" role="presentation">
                            <button class="nav-link {{ $loop->first ? 'active' : '' }}" id="konten-grup-{{ $loop->index }}-tab"
                                data-bs-toggle="tab" data-bs-target="#konten-grup-{{ $loop->index }}" type="button" role="tab"
                                aria-selected="{{ $loop->first ? 'true' : 'false' }}">
                                {{ $grup }} <span class="badge bg-primary rounded-pill">{{ $items->sum(fn ($tab) => $perTab->get($tab->id, collect())->count()) }}</span>
                            </button>
                        </li>
                    @endforeach
                </ul>
                <div class="tab-content" id="kontenGrupContent">
                    @foreach ($tabs as $grup => $items)
                        <div class="tab-pane fade {{ $loop->first ? 'show active' : '' }}" id="konten-grup-{{ $loop->index }}"
                            role="tabpanel" aria-labelledby="konten-grup-{{ $loop->index }}-tab">
                            <ul class="nav nav-tabs konten-tabs flex-nowrap mt-3" role="tablist">
                                @foreach ($items as $tab)
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link {{ $loop->first ? 'active' : '' }}" id="konten-tab-{{ $tab->id }}-button"
                                            data-bs-toggle="tab" data-bs-target="#konten-tab-{{ $tab->id }}-panel" type="button" role="tab"
                                            aria-selected="{{ $loop->first ? 'true' : 'false' }}">
                                            <span class="konten-tab-dot" style="background-color: {{ $tab->warna }}"></span>{{ $tab->nama }}
                                            <span class="badge bg-success rounded-pill">{{ $perTab->get($tab->id, collect())->count() }}</span>
                                        </button>
                                    </li>
                                @endforeach
                            </ul>
                            <div class="tab-content pt-3">
                                @foreach ($items as $tab)
                                    <div class="tab-pane fade {{ $loop->first ? 'show active' : '' }}" id="konten-tab-{{ $tab->id }}-panel"
                                        role="tabpanel" aria-labelledby="konten-tab-{{ $tab->id }}-button">
                                        @include('admin.konten.partials.daftar', ['kontenItems' => $perTab->get($tab->id, collect())])
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
            <div class="card-header"><h5 class="mb-0">Belum Ada Tab <span class="badge bg-secondary">{{ $belumAdaTab->count() }}</span></h5></div>
            <div class="card-body">
                @include('admin.konten.partials.daftar', ['kontenItems' => $belumAdaTab])
            </div>
        </div>
    @endif
    @if ($allTabs->isNotEmpty())
        @include('admin.konten.partials.tambah-modal', ['modalAsal' => 'proses', 'modalTanggalDefault' => now()->toDateString()])
    @endif
    @include('admin.konten.partials.edit-modal', ['modalAsal' => 'proses'])
@endsection

@push('after-styles')
    <style>
        .konten-tabs { overflow-x: auto; overflow-y: hidden; max-width: 100%; scrollbar-width: thin; }
        .konten-tabs .nav-item { flex-shrink: 0; }
        .konten-tabs .nav-link { white-space: nowrap; }
        .konten-tab-dot { display: inline-block; width: .65rem; height: .65rem; border-radius: 50%; margin-right: .4rem; }
        .konten-search { width: 260px; max-width: 100%; }
        .konten-table { min-width: 820px; }
        .konten-table thead th { white-space: nowrap; }
        .konten-row[hidden] { display: none; }
        .konten-row__topik { display: block; max-width: 220px; padding: 0; border: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; background: transparent; color: #0d6efd; font-weight: 600; text-align: left; cursor: pointer; }
        .konten-row__topik:hover { text-decoration: underline; }
        .konten-row__tujuan { min-width: 180px; max-width: 320px; white-space: pre-wrap; overflow-wrap: anywhere; }
        .konten-row__actions { display: flex; align-items: center; gap: .5rem; }
        .konten-row__actions form { margin: 0; }
        .konten-row__status { width: 180px; }
        .konten-row__next { display: flex; align-items: center; gap: .25rem; white-space: nowrap; }
        .konten-row__next-label { display: inline-block; max-width: 110px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    </style>
@endpush

@push('after-scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const search = document.getElementById('cariKonten');
            const savedGroup = sessionStorage.getItem('kontenGrupTab');
            const savedTab = sessionStorage.getItem('kontenProsesTab');
            document.querySelectorAll('#kontenGrupTab [data-bs-toggle="tab"]').forEach(function (button) {
                button.addEventListener('shown.bs.tab', function () { sessionStorage.setItem('kontenGrupTab', button.id); });
            });
            document.querySelectorAll('#kontenGrupContent .tab-pane .nav-tabs [data-bs-toggle="tab"]').forEach(function (button) {
                button.addEventListener('shown.bs.tab', function () { sessionStorage.setItem('kontenProsesTab', button.id); });
            });

            function saveTargetStatus(statusId) {
                const button = document.getElementById('konten-tab-' + statusId + '-button');
                const groupPane = button?.closest('#kontenGrupContent > .tab-pane');
                if (!button || !groupPane) return;
                sessionStorage.setItem('kontenGrupTab', groupPane.id + '-tab');
                sessionStorage.setItem('kontenProsesTab', button.id);
            }

            document.querySelectorAll('[data-konten-status-select]').forEach(function (select) {
                select.addEventListener('change', function () {
                    if (!select.value || select.dataset.submitting) return;
                    select.dataset.submitting = '1';
                    saveTargetStatus(select.value);
                    select.form.requestSubmit();
                });
            });

            document.querySelectorAll('[data-konten-next-form]').forEach(function (form) {
                form.addEventListener('submit', function () {
                    saveTargetStatus(form.querySelector('[name="konten_tab_id"]').value);
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
            search.addEventListener('input', function () {
                const value = search.value.trim().toLocaleLowerCase();
                document.querySelectorAll('.konten-row').forEach(function (row) {
                    row.hidden = !row.dataset.search.includes(value);
                });
            });
        });
    </script>
@endpush
