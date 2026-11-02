@extends('layouts.app')

@section('title', 'Setup Desain')

@section('content')
    <div class="mb-3">
        <h4 class="mb-1">Setup Desain</h4>
        <p class="text-muted mb-0">Atur kategori dan sub kategori untuk dashboard proses desain. Seret ikon <i class="bx bx-move"></i> untuk mengubah urutan kategori maupun sub kategori.</p>
    </div>
    @include('layouts.includes.messages')

    <div class="card mb-4">
        <div class="card-header"><h5 class="mb-0">Tambah Kategori</h5></div>
        <div class="card-body">
            <form method="POST" action="{{ route('desain.group.store') }}">
                @csrf
                <div class="row g-3 align-items-end">
                    <div class="col-md-10">
                        <label class="form-label" for="nama-kategori">Nama Kategori</label>
                        <input id="nama-kategori" name="nama" type="text" class="form-control" maxlength="255" required value="{{ old('nama') }}" placeholder="Contoh: Produksi">
                    </div>
                    <div class="col-md-2"><button class="btn btn-primary w-100" type="submit">Tambah</button></div>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><h5 class="mb-0">Kategori &amp; Sub Kategori</h5></div>
        <div class="card-body">
            <div class="desain-group-sortable">
                @forelse ($groups as $group)
                    @php $items = $tabs->get($group->nama, collect()); @endphp
                    <div class="desain-group-card mb-4" data-group-id="{{ $group->id }}">
                        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 pb-2 border-bottom">
                            <div class="d-flex align-items-center gap-2">
                                <span class="desain-group-drag" title="Seret untuk mengurutkan kategori" style="cursor: move"><i class="bx bx-move"></i></span>
                                <h6 class="mb-0">{{ $group->nama }}</h6>
                                <span class="badge bg-secondary">{{ $items->count() }} sub kategori</span>
                                <span class="badge bg-light text-dark desain-group-urutan">Urutan {{ $group->urutan }}</span>
                            </div>
                            <div class="d-flex gap-1">
                                <button type="button" class="btn btn-info btn-sm text-white" data-bs-toggle="modal" data-bs-target="#edit-group-{{ $group->id }}">Edit Kategori</button>
                                <form method="POST" action="{{ route('desain.group.destroy', $group) }}" onsubmit="return confirm('Hapus kategori ini?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-outline-danger btn-sm">Hapus</button>
                                </form>
                            </div>
                        </div>

                        <div class="table-responsive mt-2">
                            <table class="table table-striped align-middle mb-0">
                                <thead><tr><th style="width: 40px"></th><th>Sub Kategori</th><th>Warna</th><th>Urutan</th><th>Aksi</th></tr></thead>
                                <tbody class="desain-sortable" data-grup="{{ $group->nama }}">
                                    @foreach ($items as $tab)
                                        <tr data-id="{{ $tab->id }}">
                                            <td class="desain-tab-drag" title="Seret untuk mengurutkan sub kategori" style="cursor: move"><i class="bx bx-move"></i></td>
                                            <td>{{ $tab->nama }}</td>
                                            <td><span class="d-inline-block rounded-circle me-1" style="width: 14px; height: 14px; background: {{ $tab->warna }}"></span>{{ $tab->warna }}</td>
                                            <td class="desain-urutan">{{ $tab->urutan }}</td>
                                            <td>
                                                <div class="d-flex gap-1">
                                                    <button type="button" class="btn btn-info btn-sm text-white" data-bs-toggle="modal" data-bs-target="#edit-tab-{{ $tab->id }}">Edit</button>
                                                    <form method="POST" action="{{ route('desain.tab.destroy', $tab) }}" onsubmit="return confirm('Hapus sub kategori ini?')">
                                                        @csrf @method('DELETE')
                                                        <button type="submit" class="btn btn-outline-danger btn-sm">Hapus</button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                    @if ($items->isEmpty())
                                        <tr><td colspan="5" class="text-center text-muted py-3">Belum ada sub kategori.</td></tr>
                                    @endif
                                </tbody>
                            </table>
                        </div>

                        <form method="POST" action="{{ route('desain.tab.store') }}" class="row g-2 align-items-end mt-3">
                            @csrf
                            <input type="hidden" name="grup" value="{{ $group->nama }}">
                            <div class="col-md-6">
                                <label class="form-label" for="nama-sub-{{ $group->id }}">Tambah Sub Kategori</label>
                                <input id="nama-sub-{{ $group->id }}" name="nama" type="text" class="form-control" maxlength="255" required placeholder="Contoh: Draft">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label" for="warna-sub-{{ $group->id }}">Warna</label>
                                <input id="warna-sub-{{ $group->id }}" name="warna" type="color" class="form-control form-control-color w-100" required value="#0d6efd">
                            </div>
                            <div class="col-md-3">
                                <button class="btn btn-outline-primary w-100" type="submit">Tambah Sub Kategori</button>
                            </div>
                        </form>
                    </div>
                @empty
                    <p class="text-muted mb-0">Belum ada kategori. Tambahkan kategori pertama di atas agar sub kategori desain dapat dibuat.</p>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Modal edit kategori --}}
    @foreach ($groups as $group)
        <div class="modal fade" id="edit-group-{{ $group->id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form method="POST" action="{{ route('desain.group.update', $group) }}">
                        @csrf @method('PATCH')
                        <div class="modal-header"><h5 class="modal-title">Edit Kategori</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button></div>
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label" for="edit-nama-kategori-{{ $group->id }}">Nama Kategori</label>
                                <input class="form-control" id="edit-nama-kategori-{{ $group->id }}" name="nama" required maxlength="255" value="{{ $group->nama }}">
                                <small class="text-muted">Mengubah nama kategori juga mengubah grup pada semua sub kategorinya.</small>
                            </div>
                        </div>
                        <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button><button type="submit" class="btn btn-primary">Simpan</button></div>
                    </form>
                </div>
            </div>
        </div>
    @endforeach

    {{-- Modal edit sub kategori --}}
    @foreach ($tabs->flatten() as $tab)
        <div class="modal fade" id="edit-tab-{{ $tab->id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form method="POST" action="{{ route('desain.tab.update', $tab) }}">
                        @csrf @method('PATCH')
                        <div class="modal-header"><h5 class="modal-title">Edit Sub Kategori</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button></div>
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label" for="edit-nama-{{ $tab->id }}">Nama Sub Kategori</label>
                                <input class="form-control" id="edit-nama-{{ $tab->id }}" name="nama" required maxlength="255" value="{{ $tab->nama }}">
                            </div>
                            <div class="mb-3">
                                <label class="form-label" for="edit-grup-{{ $tab->id }}">Kategori</label>
                                <select class="form-select" id="edit-grup-{{ $tab->id }}" name="grup" required>
                                    @foreach ($groups as $pilihan)
                                        <option value="{{ $pilihan->nama }}" {{ $pilihan->nama === $tab->grup ? 'selected' : '' }}>{{ $pilihan->nama }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="form-label" for="edit-warna-{{ $tab->id }}">Warna</label>
                                <input class="form-control form-control-color" id="edit-warna-{{ $tab->id }}" name="warna" type="color" required value="{{ $tab->warna }}">
                            </div>
                        </div>
                        <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button><button type="submit" class="btn btn-primary">Simpan</button></div>
                    </form>
                </div>
            </div>
        </div>
    @endforeach
@endsection

@push('after-scripts')
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
    <script>
        document.querySelectorAll('.desain-group-sortable').forEach(function (container) {
            new Sortable(container, {
                handle: '.desain-group-drag',
                animation: 150,
                onEnd: function () {
                    const ids = Array.from(container.querySelectorAll('.desain-group-card')).map(function (card) {
                        return card.dataset.groupId;
                    });

                    fetch('{{ route('desain.group.reorder') }}', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
                        body: JSON.stringify({ ids: ids })
                    }).then(function (response) {
                        if (!response.ok) throw new Error('Gagal menyimpan urutan kategori.');
                        container.querySelectorAll('.desain-group-urutan').forEach(function (badge, index) {
                            badge.textContent = 'Urutan ' + (index + 1);
                        });
                    }).catch(function () { window.location.reload(); });
                }
            });
        });

        document.querySelectorAll('.desain-sortable').forEach(function (tbody) {
            new Sortable(tbody, {
                handle: '.desain-tab-drag',
                animation: 150,
                onEnd: function () {
                    const ids = Array.from(tbody.querySelectorAll('tr')).map(function (row) {
                        return row.dataset.id;
                    }).filter(Boolean);
                    if (!ids.length) return;

                    fetch('{{ route('desain.tab.reorder') }}', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
                        body: JSON.stringify({ grup: tbody.dataset.grup, ids: ids })
                    }).then(function (response) {
                        if (!response.ok) throw new Error('Gagal menyimpan urutan sub kategori.');
                        tbody.querySelectorAll('.desain-urutan').forEach(function (cell, index) { cell.textContent = index + 1; });
                    }).catch(function () { window.location.reload(); });
                }
            });
        });
    </script>
@endpush
