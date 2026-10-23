@extends('layouts.app')

@section('title', 'Setup Konten')

@section('content')
    <div class="mb-3">
        <h4 class="mb-1">Setup Konten</h4>
        <p class="text-muted mb-0">Atur grup dan urutan tab untuk dashboard proses konten. Konten baru otomatis masuk tab pertama sesuai urutan. Seret baris untuk mengubah urutan dalam grup.</p>
    </div>
    @include('layouts.includes.messages')
    @if ($errors->any())
        <div class="alert alert-danger">{{ $errors->first() }}</div>
    @endif

    <div class="card mb-4">
        <div class="card-header"><h5 class="mb-0">Tambah Tab</h5></div>
        <div class="card-body">
            <form method="POST" action="{{ route('konten.tab.store') }}">
                @csrf
                <div class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label" for="nama">Nama Tab</label>
                        <input id="nama" name="nama" type="text" class="form-control" maxlength="255" required value="{{ old('nama') }}" placeholder="Contoh: Draft">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="grup">Grup</label>
                        <input id="grup" name="grup" type="text" class="form-control" maxlength="255" required value="{{ old('grup') }}" list="grup-konten" placeholder="Contoh: Produksi">
                        <datalist id="grup-konten">
                            @foreach ($tabs->keys() as $grup)
                                <option value="{{ $grup }}"></option>
                            @endforeach
                        </datalist>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label" for="warna">Warna</label>
                        <input id="warna" name="warna" type="color" class="form-control form-control-color w-100" required value="{{ old('warna', '#0d6efd') }}">
                    </div>
                    <div class="col-md-2"><button class="btn btn-primary w-100" type="submit">Tambah</button></div>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><h5 class="mb-0">Daftar Tab</h5></div>
        <div class="card-body">
            @forelse ($tabs as $grup => $items)
                <div class="mb-4">
                    <h6 class="fw-bold">{{ $grup }}</h6>
                    <div class="table-responsive">
                        <table class="table table-striped align-middle">
                            <thead><tr><th style="width: 40px"></th><th>Nama</th><th>Warna</th><th>Urutan</th><th>Aksi</th></tr></thead>
                            <tbody class="konten-sortable" data-grup="{{ $grup }}">
                                @foreach ($items as $tab)
                                    <tr data-id="{{ $tab->id }}">
                                        <td class="konten-drag" title="Seret untuk mengurutkan" style="cursor: move"><i class="bx bx-move"></i></td>
                                        <td>{{ $tab->nama }}</td>
                                        <td><span class="d-inline-block rounded-circle me-1" style="width: 14px; height: 14px; background: {{ $tab->warna }}"></span>{{ $tab->warna }}</td>
                                        <td class="konten-urutan">{{ $tab->urutan }}</td>
                                        <td>
                                            <div class="d-flex gap-1">
                                                <button type="button" class="btn btn-info btn-sm text-white" data-bs-toggle="modal" data-bs-target="#edit-tab-{{ $tab->id }}">Edit</button>
                                                <form method="POST" action="{{ route('konten.tab.destroy', $tab) }}" onsubmit="return confirm('Hapus tab ini?')">
                                                    @csrf @method('DELETE')
                                                    <button type="submit" class="btn btn-outline-danger btn-sm">Hapus</button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @empty
                <p class="text-muted mb-0">Belum ada tab. Tambahkan tab pertama di atas agar jadwal konten dapat dibuat.</p>
            @endforelse
        </div>
    </div>

    @foreach ($tabs as $grup => $items)
        @foreach ($items as $tab)
            <div class="modal fade" id="edit-tab-{{ $tab->id }}" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <form method="POST" action="{{ route('konten.tab.update', $tab) }}">
                            @csrf @method('PATCH')
                            <div class="modal-header"><h5 class="modal-title">Edit Tab Konten</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button></div>
                            <div class="modal-body">
                                <div class="mb-3"><label class="form-label" for="edit-nama-{{ $tab->id }}">Nama Tab</label><input class="form-control" id="edit-nama-{{ $tab->id }}" name="nama" required maxlength="255" value="{{ $tab->nama }}"></div>
                                <div class="mb-3"><label class="form-label" for="edit-grup-{{ $tab->id }}">Grup</label><input class="form-control" id="edit-grup-{{ $tab->id }}" name="grup" required maxlength="255" list="grup-konten" value="{{ $tab->grup }}"></div>
                                <div><label class="form-label" for="edit-warna-{{ $tab->id }}">Warna</label><input class="form-control form-control-color" id="edit-warna-{{ $tab->id }}" name="warna" type="color" required value="{{ $tab->warna }}"></div>
                            </div>
                            <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button><button type="submit" class="btn btn-primary">Simpan</button></div>
                        </form>
                    </div>
                </div>
            </div>
        @endforeach
    @endforeach
@endsection

@push('after-scripts')
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
    <script>
        document.querySelectorAll('.konten-sortable').forEach(function (tbody) {
            new Sortable(tbody, {
                handle: '.konten-drag',
                animation: 150,
                onEnd: function () {
                    const ids = Array.from(tbody.querySelectorAll('tr')).map(function (row) { return row.dataset.id; });
                    fetch('{{ route('konten.tab.reorder') }}', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
                        body: JSON.stringify({ grup: tbody.dataset.grup, ids: ids })
                    }).then(function (response) {
                        if (!response.ok) throw new Error('Gagal menyimpan urutan.');
                        tbody.querySelectorAll('.konten-urutan').forEach(function (cell, index) { cell.textContent = index + 1; });
                    }).catch(function () { window.location.reload(); });
                }
            });
        });
    </script>
@endpush
