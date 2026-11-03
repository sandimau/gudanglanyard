@extends('layouts.app')

@section('title')
    Komplain
@endsection

@section('content')
    <div class="komplain-page">
        @include('layouts.includes.messages')

        <div class="card border-0 shadow-sm mb-3">
            <div class="card-body p-3 p-md-4">
                <div class="mb-3">
                    <h5 class="mb-1 fw-semibold">Komplain Konsumen</h5>
                    <div class="text-secondary small">
                        Daftar komplain offline dan online. Komplain yang disetujui otomatis menambah produk pengganti
                        tanpa menambah total order, dan biaya penggantian dicatat sebagai kerugian.
                    </div>
                </div>
                <div class="overflow-auto mb-3">
                    <form action="{{ route('komplain.index') }}" method="get" class="d-grid gap-2 align-items-center"
                        style="grid-template-columns: minmax(220px, 1fr) 150px 160px auto; min-width: 600px;">
                        <input type="text" name="q" value="{{ $search }}" class="form-control form-control-sm w-100"
                            placeholder="Cari konsumen / nota / produk" style="min-width: 0;">
                        <select name="tipe" class="form-select form-select-sm w-100" style="min-width: 0;">
                            <option value="">Semua jenis</option>
                            <option value="offline" {{ $tipe === 'offline' ? 'selected' : '' }}>Offline</option>
                            <option value="online" {{ $tipe === 'online' ? 'selected' : '' }}>Online</option>
                        </select>
                        <select name="status" class="form-select form-select-sm w-100" style="min-width: 0;">
                            <option value="">Semua status</option>
                            <option value="pending" {{ $status === 'pending' ? 'selected' : '' }}>Menunggu</option>
                            <option value="approved" {{ $status === 'approved' ? 'selected' : '' }}>Disetujui</option>
                            <option value="rejected" {{ $status === 'rejected' ? 'selected' : '' }}>Ditolak</option>
                        </select>
                        <button type="submit" class="btn btn-primary btn-sm text-nowrap">Filter</button>
                    </form>
                </div>

                <div class="row g-2">
                    <div class="col-6 col-md-3">
                        <div class="border rounded p-2 h-100">
                            <div class="text-secondary small">Menunggu</div>
                            <div class="fs-5 fw-semibold">{{ number_format($stats['pending'], 0, ',', '.') }}</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="border rounded p-2 h-100">
                            <div class="text-secondary small">Disetujui</div>
                            <div class="fs-5 fw-semibold text-success">{{ number_format($stats['approved'], 0, ',', '.') }}</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="border rounded p-2 h-100">
                            <div class="text-secondary small">Ditolak</div>
                            <div class="fs-5 fw-semibold text-danger">{{ number_format($stats['rejected'], 0, ',', '.') }}</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="border rounded p-2 h-100">
                            <div class="text-secondary small">Total Kerugian</div>
                            <div class="fs-5 fw-semibold text-danger">
                                Rp {{ number_format($stats['kerugian'], 0, ',', '.') }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3">Tanggal</th>
                                <th>Konsumen</th>
                                <th>Jenis</th>
                                <th class="text-center">Pengganti</th>
                                <th class="text-end">Kerugian</th>
                                <th>Status</th>
                                <th class="text-end pe-3">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($komplains as $komplain)
                                <tr>
                                    <td class="ps-3 text-nowrap small">
                                        {{ $komplain->created_at?->format('d-m-Y H:i') }}
                                    </td>
                                    <td class="small">
                                        @php
                                            $detailUrl = $komplain->project_mp_detail_id && $komplain->projectMp
                                                ? route('projectmp.detail', $komplain->project_mp_id)
                                                : ($komplain->order
                                                    ? route('order.detail', $komplain->order_id)
                                                    : ($komplain->projectMp
                                                        ? route('projectmp.detail', $komplain->project_mp_id)
                                                        : null));
                                        @endphp
                                        @if ($detailUrl)
                                            <a href="{{ $detailUrl }}" class="popup fw-semibold text-decoration-none">
                                                {{ $komplain->nama_konsumen ?: ($komplain->kontak->nama ?? '-') }}
                                            </a>
                                        @else
                                            <div class="fw-semibold">{{ $komplain->nama_konsumen ?: ($komplain->kontak->nama ?? '-') }}</div>
                                        @endif
                                        @if ($komplain->user)
                                            <div class="text-muted" style="font-size:.72rem;">
                                                oleh {{ $komplain->user->name }}
                                            </div>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge {{ strtolower($komplain->tipe) === 'online' ? 'bg-primary' : 'bg-secondary' }}">
                                            {{ $komplain->tipe_label }}
                                        </span>
                                    </td>
                                    <td class="text-center fw-semibold">
                                        <a href="{{ route('komplain.show', $komplain) }}" class="komplain-show-link" title="Lihat detail komplain">
                                            {{ $komplain->status === 'approved' ? number_format($komplain->jumlah, 0, ',', '.') : '0' }} pcs
                                        </a>
                                    </td>
                                    <td class="text-end text-nowrap">
                                        @if (strtolower($komplain->status) === 'approved')
                                            <span class="text-danger fw-semibold">
                                                Rp {{ number_format($komplain->kerugian, 0, ',', '.') }}
                                            </span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if (strtolower($komplain->status) === 'approved')
                                            <span class="badge bg-success">Disetujui</span>
                                        @elseif (strtolower($komplain->status) === 'rejected')
                                            <span class="badge bg-danger">Ditolak</span>
                                        @else
                                            <span class="badge bg-warning text-dark">Menunggu</span>
                                        @endif
                                        @if ($komplain->approved_at)
                                            <div class="text-muted small text-nowrap">
                                                {{ $komplain->approved_at->format('d-m-Y H:i') }}
                                            </div>
                                        @endif
                                    </td>
                                    <td class="text-end pe-3 text-nowrap">
                                        @can('komplain_approve')
                                            @if ($komplain->is_pending)
                                                <form action="{{ route('komplain.approve', $komplain->id) }}" method="post"
                                                    class="d-inline"
                                                    onsubmit="return confirm('Setujui komplain ini? Produk pengganti akan ditambahkan ke nota {{ $komplain->nota }}.');">
                                                    {{ csrf_field() }}
                                                    {{ method_field('patch') }}
                                                    <button type="submit" class="btn btn-success btn-sm">ACC</button>
                                                </form>
                                                <form action="{{ route('komplain.reject', $komplain->id) }}" method="post"
                                                    class="d-inline komplain-reject-form">
                                                    {{ csrf_field() }}
                                                    {{ method_field('patch') }}
                                                    <input type="hidden" name="catatan_admin">
                                                    <button type="submit" class="btn btn-outline-danger btn-sm">Tolak</button>
                                                </form>
                                            @else
                                                <span class="text-muted small">
                                                    {{ $komplain->approvedBy->name ?? '-' }}
                                                </span>
                                            @endif
                                        @endcan
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">Belum ada komplain.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @if ($komplains->hasPages())
                <div class="card-footer bg-white border-0">
                    {{ $komplains->links() }}
                </div>
            @endif
        </div>

        @include('admin.orders.partials.detail-order-modal')
        @include('admin.projectmps.partials.detail-projectmp-modal')

        <div class="modal fade" id="detailKomplainModal" tabindex="-1" aria-labelledby="detailKomplainModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-fullscreen-lg-down modal-dialog-scrollable modal-dialog-centered modal-xxl">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="detailKomplainModalLabel">Detail Komplain</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                    </div>
                    <div class="modal-body" id="detailKomplainBody"></div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('after-styles')
    <style>
        @media (min-width: 992px) {
            #detailOrderModal .modal-dialog,
            #detailProjectMpModal .modal-dialog,
            #detailKomplainModal .modal-dialog {
                width: calc(100vw - 24px);
                max-width: none;
                margin: 12px auto;
            }
        }
    </style>
@endpush

@push('after-scripts')
    <script>
        @include('admin.orders.partials.detail-order-modal-js')
        @include('admin.projectmps.partials.detail-projectmp-modal-js')

        (function() {
            var modalEl = document.getElementById('detailKomplainModal');
            var bodyEl = document.getElementById('detailKomplainBody');
            var request;

            document.addEventListener('click', function(event) {
                var link = event.target.closest('.komplain-show-link');
                if (!link || !modalEl || typeof bootstrap === 'undefined') return;

                event.preventDefault();
                if (request) request.abort();
                request = new AbortController();
                bodyEl.innerHTML = '<div class="text-center py-5"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Memuat...</span></div></div>';
                bootstrap.Modal.getOrCreateInstance(modalEl).show();

                fetch(link.href, { signal: request.signal, credentials: 'same-origin' })
                    .then(function(response) {
                        if (!response.ok) throw new Error('Gagal memuat detail komplain.');
                        return response.text();
                    })
                    .then(function(html) {
                        var doc = new DOMParser().parseFromString(html, 'text/html');
                        var content = doc.querySelector('.body .container-fluid .mb-4');
                        if (!content) throw new Error('Detail komplain tidak tersedia.');
                        bodyEl.innerHTML = content.innerHTML;
                    })
                    .catch(function(error) {
                        if (error.name !== 'AbortError') {
                            bodyEl.innerHTML = '<div class="alert alert-danger mb-0"></div>';
                            bodyEl.firstElementChild.textContent = error.message;
                        }
                    });
            });

            modalEl.addEventListener('click', function(event) {
                if (event.target.closest('.komplain-back-link')) {
                    event.preventDefault();
                    bootstrap.Modal.getInstance(modalEl).hide();
                }
            });

            modalEl.addEventListener('hidden.bs.modal', function() {
                if (request) request.abort();
                bodyEl.innerHTML = '';
            });
        })();

        document.querySelectorAll('.komplain-reject-form').forEach(function(form) {
            form.addEventListener('submit', function(event) {
                var catatan = window.prompt('Alasan menolak komplain (opsional):', '');
                if (catatan === null) {
                    event.preventDefault();
                    return;
                }

                form.querySelector('input[name="catatan_admin"]').value = catatan;
            });
        });
    </script>
@endpush
