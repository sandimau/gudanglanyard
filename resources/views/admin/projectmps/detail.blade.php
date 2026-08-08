@extends('layouts.app')

@section('title')
    Detail Order
@endsection

@section('content')
    @php
        $judulProject = trim(
            ($projectMp->nota ?? '') .
                ' | ' .
                ($marketplace->nama ?? '') .
                ' - ' .
                ($projectMp->konsumen ?? ''),
        );
        $isCustom = $projectMp->buffer && (int) $projectMp->buffer->custom === 1;
        $mpWarna = $marketplace->warna ?? '#6c757d';
    @endphp

    <div class="projectmp-detail-page">
        <nav aria-label="breadcrumb" class="mb-3">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item">
                    <a href="{{ route('projectmp.dashboard') }}">Dashboard</a>
                </li>
                <li class="breadcrumb-item active" aria-current="page">Order Detail</li>
            </ol>
        </nav>

        @include('layouts.includes.messages')

        <div class="card projectmp-detail-card border-0 shadow-sm mb-3">
            <div class="card-body p-3 p-md-4">
                <div class="d-flex flex-wrap align-items-start justify-content-between gap-3 mb-3">
                    <div class="projectmp-detail-heading min-w-0">
                        <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
                            @if ($marketplace?->nama)
                                <span class="badge projectmp-mp-badge"
                                    style="background-color: {{ str_starts_with($mpWarna, '#') ? $mpWarna : '#' . $mpWarna }}">
                                    {{ $marketplace->nama }}
                                </span>
                            @endif
                            <span class="projectmp-nota">{{ $projectMp->nota }}</span>
                            <button type="button" class="btn btn-sm btn-light border copy-drive-link"
                                data-copy-text="{{ $judulProject }}" title="Salin judul">
                                <i class='bx bx-copy'></i>
                            </button>
                        </div>
                        <div class="projectmp-konsumen text-secondary">
                            <i class='bx bx-user'></i>
                            {{ $projectMp->konsumen ?: 'Tanpa nama konsumen' }}
                        </div>
                    </div>

                    @if ($isCustom)
                        <div class="projectmp-pemproses-box">
                            <label class="form-label small text-secondary mb-1">Status</label>
                            <form action="{{ route('projectMp.pemproses', $projectMp->id) }}" method="post"
                                class="projectmp-detail-ajax-form">
                                {{ csrf_field() }}
                                {{ method_field('patch') }}
                                <select class="form-select form-select-sm" aria-label="Pilih pemproses"
                                    name="pemproses_id" onchange="this.form.requestSubmit()">
                                    <option value="">- pilih -</option>
                                    @foreach (($pemprosesUtama ?? collect()) as $entry)
                                        <option value="{{ $entry->id }}"
                                            {{ $projectMp->pemproses_id == $entry->id ? 'selected' : '' }}>
                                            {{ $entry->nama }}</option>
                                    @endforeach
                                </select>
                            </form>
                        </div>
                    @endif
                </div>

                <div class="projectmp-summary-grid">
                    <div class="projectmp-summary-item">
                        <span class="label">Ongkir</span>
                        <span class="value">{{ number_format($projectMp->ongkir, 0, ',', '.') }}</span>
                    </div>
                    <div class="projectmp-summary-item">
                        <span class="label">Diskon</span>
                        <span class="value">{{ number_format($projectMp->diskon, 0, ',', '.') }}</span>
                    </div>
                    <div class="projectmp-summary-item is-emphasis">
                        <span class="label">Total</span>
                        <span class="value">{{ number_format($projectMp->total, 0, ',', '.') }}</span>
                    </div>
                    <div class="projectmp-summary-item">
                        <span class="label">Pembayaran</span>
                        <span class="value">{{ number_format($projectMp->bayar, 0, ',', '.') }}</span>
                    </div>
                    <div class="projectmp-summary-item {{ ($projectMp->kekurangan ?? 0) > 0 ? 'is-warn' : '' }}">
                        <span class="label">Kekurangan</span>
                        <span class="value">{{ number_format($projectMp->kekurangan, 0, ',', '.') }}</span>
                    </div>
                </div>

                @if (!empty($projectMp->keterangan))
                    <div class="projectmp-keterangan mt-3">
                        <div class="small text-secondary mb-1">Keterangan</div>
                        <p class="mb-0">{{ $projectMp->keterangan }}</p>
                    </div>
                @endif
            </div>
        </div>

        <div class="card projectmp-detail-card border-0 shadow-sm mb-3">
            <div class="card-header bg-white border-0 pt-3 px-3 px-md-4 pb-0">
                <div class="d-flex align-items-center justify-content-between gap-2">
                    <h6 class="mb-0 fw-semibold">Produk Marketplace</h6>
                    <span class="badge text-bg-light text-secondary border">{{ $projectMpdetails->count() }} item</span>
                </div>
            </div>
            <div class="card-body px-3 px-md-4 pt-3">
                <div class="table-responsive projectmp-table-wrap">
                    <table class="table projectmp-table align-middle mb-0" id="myTable">
                        <thead>
                            <tr>
                                <th>Produk</th>
                                <th>Tema</th>
                                <th class="text-center">Jml</th>
                                <th class="text-end">Harga</th>
                                <th class="text-end">Subtotal</th>
                                @if ($isCustom)
                                    <th>Posisi</th>
                                    <th>Pemproses</th>
                                @endif
                                <th class="text-center">Gambar</th>
                                <th>Deadline</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($projectMpdetails as $detail)
                                <tr>
                                    <td class="projectmp-produk-cell">
                                        {{ $detail->produk->namaLengkap ?? '-' }}
                                    </td>
                                    <td class="text-secondary small">{{ $detail->tema ?: '-' }}</td>
                                    <td class="text-center fw-semibold">{{ $detail->jumlah }}</td>
                                    <td class="text-end text-nowrap">{{ number_format($detail->harga) }}</td>
                                    <td class="text-end text-nowrap fw-semibold">
                                        {{ number_format($detail->harga * $detail->jumlah) }}
                                    </td>
                                    @if ($isCustom)
                                        <td style="min-width: 8.5rem;">
                                            @if ($canEditLimited && !$isMarketingOnly && !$isProduksiLevel)
                                                <form action="{{ route('projectMpDetail.status', $detail->id) }}"
                                                    method="post" class="projectmp-detail-ajax-form">
                                                    {{ csrf_field() }}
                                                    {{ method_field('patch') }}
                                                    <select class="form-select form-select-sm"
                                                        name="produksi_id" onchange="this.form.requestSubmit()">
                                                        @foreach (\App\Models\Produksi::statusPathForDetail($detail) as $entry)
                                                            <option value="{{ $entry->id }}"
                                                                {{ $detail->produksi_id == $entry->id ? 'selected' : '' }}>
                                                                {{ $entry->nama }}</option>
                                                        @endforeach
                                                    </select>
                                                </form>
                                            @else
                                                <span class="badge text-bg-light border">
                                                    {{ $detail->produksi->nama ?? '-' }}
                                                </span>
                                            @endif
                                        </td>
                                        <td style="min-width: 8rem;">
                                            <form action="{{ route('projectMpDetail.pemproses', $detail->id) }}"
                                                method="post" class="projectmp-detail-ajax-form">
                                                {{ csrf_field() }}
                                                {{ method_field('patch') }}
                                                <select class="form-select form-select-sm" aria-label="Pilih label"
                                                    name="pemproses_id" onchange="this.form.requestSubmit()">
                                                    <option value="">- pilih -</option>
                                                    @foreach (($pemprosesSetting ?? collect()) as $entry)
                                                        <option value="{{ $entry->id }}"
                                                            {{ $detail->pemproses_id == $entry->id ? 'selected' : '' }}>
                                                            {{ $entry->nama }}</option>
                                                    @endforeach
                                                </select>
                                            </form>
                                        </td>
                                    @endif
                                    <td class="text-center">
                                        @if ($detail->gambar)
                                            <a href="#" class="projectmp-detail-image-thumb projectmp-thumb"
                                                data-image-src="{{ asset('uploads/projectMp/' . $detail->gambar) }}"
                                                data-edit-url="{{ route('projectMpDetail.editGambar', $detail->id) }}">
                                                <img src="{{ asset('uploads/projectMp/' . $detail->gambar) }}"
                                                    alt="Gambar produk">
                                            </a>
                                        @else
                                            <a href="{{ route('projectMpDetail.gambar', $detail->id) }}"
                                                class="btn btn-sm btn-success text-white" title="Upload gambar">
                                                <i class='bx bx-image-alt'></i>
                                            </a>
                                        @endif
                                    </td>
                                    <td class="text-nowrap">
                                        <a class="projectmp-deadline-link"
                                            href="{{ route('projectMpDetail.edit', $detail->id) }}">
                                            @if ($detail->deadline)
                                                <i class='bx bx-calendar'></i>
                                                {{ \Carbon\Carbon::parse($detail->deadline)->format('d-m-Y') }}
                                            @else
                                                <span class="text-muted">Belum ada</span>
                                            @endif
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ $isCustom ? 9 : 7 }}" class="text-center text-muted py-4">
                                        Belum ada produk
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="card projectmp-detail-card border-0 shadow-sm mb-3">
            <div class="card-header bg-white border-0 pt-3 px-3 px-md-4 pb-0">
                <div class="d-flex justify-content-between align-items-center gap-2">
                    <div class="d-flex align-items-center gap-2">
                        <h6 class="mb-0 fw-semibold">Order Tambahan (Offline)</h6>
                        <span class="badge text-bg-light text-secondary border">{{ $orderDetails->count() }} item</span>
                    </div>
                    @if ($canAddOrderProduk)
                        <a href="{{ route('projectMpOrder.add', $projectMp->id) }}"
                            class="btn btn-success btn-sm rounded-pill text-white">
                            <i class='bx bx-plus-circle'></i> tambah
                        </a>
                    @endif
                </div>
            </div>
            <div class="card-body px-3 px-md-4 pt-3">
                <div class="table-responsive projectmp-table-wrap">
                    <table class="table projectmp-table align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Produk</th>
                                <th>Tema</th>
                                <th class="text-center">Jml</th>
                                <th class="text-end">Harga</th>
                                <th class="text-end">Subtotal</th>
                                <th>Spesifikasi</th>
                                <th>Status</th>
                                <th>Label</th>
                                <th class="text-center">Gambar</th>
                                <th>Deadline</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($orderDetails as $detail)
                                <tr>
                                    <td class="projectmp-produk-cell">{{ $detail->produk->namaLengkap ?? '-' }}</td>
                                    <td class="text-secondary small">{{ $detail->tema ?: '-' }}</td>
                                    <td class="text-center fw-semibold">{{ $detail->jumlah }}</td>
                                    <td class="text-end text-nowrap">{{ number_format($detail->harga) }}</td>
                                    <td class="text-end text-nowrap fw-semibold">
                                        {{ number_format($detail->harga * $detail->jumlah) }}
                                    </td>
                                    <td class="small text-secondary">
                                        @foreach ($detail->spek as $spek)
                                            <div><span class="fw-semibold text-dark">{{ $spek->nama }}:</span>
                                                {{ $spek->pivot->keterangan }}</div>
                                        @endforeach
                                        @if (!empty($detail->keterangan))
                                            <div class="text-danger mt-1">
                                                <span class="fw-semibold">keterangan:</span> {{ $detail->keterangan }}
                                            </div>
                                        @endif
                                        @if ($detail->spek->isEmpty() && empty($detail->keterangan))
                                            -
                                        @endif
                                    </td>
                                    <td style="min-width: 8.5rem;">
                                        @if ($canAddOrderProduk)
                                            <form action="{{ route('orderDetail.status', $detail->id) }}" method="post"
                                                class="order-detail-ajax-form">
                                                {{ csrf_field() }}
                                                {{ method_field('patch') }}
                                                <select class="form-select form-select-sm" name="produksi_id"
                                                    onchange="this.form.requestSubmit()">
                                                    @foreach (\App\Models\Produksi::statusPathForDetail($detail) as $entry)
                                                        <option value="{{ $entry->id }}"
                                                            {{ $detail->produksi_id == $entry->id ? 'selected' : '' }}>
                                                            {{ $entry->nama }}</option>
                                                    @endforeach
                                                </select>
                                            </form>
                                        @else
                                            <span class="badge text-bg-light border">
                                                {{ $detail->produksi->nama ?? '-' }}
                                            </span>
                                        @endif
                                    </td>
                                    <td style="min-width: 8rem;">
                                        @if ($canAddOrderProduk)
                                            <form action="{{ route('orderDetail.pemproses', $detail->id) }}"
                                                method="post" class="order-detail-ajax-form">
                                                {{ csrf_field() }}
                                                {{ method_field('patch') }}
                                                <select class="form-select form-select-sm" aria-label="Pilih label"
                                                    name="pemproses_id" onchange="this.form.requestSubmit()">
                                                    <option value="">- pilih -</option>
                                                    @foreach (($pemprosesSetting ?? collect()) as $entry)
                                                        <option value="{{ $entry->id }}"
                                                            {{ $detail->pemproses_id == $entry->id ? 'selected' : '' }}>
                                                            {{ $entry->nama }}</option>
                                                    @endforeach
                                                </select>
                                            </form>
                                        @else
                                            {{ $detail->pemproses->nama ?? '-' }}
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @if ($detail->gambar)
                                            @if ($canAddOrderProduk)
                                                <a href="{{ route('orderDetail.editGambar', $detail->id) }}"
                                                    class="projectmp-thumb">
                                                    <img src="{{ asset('uploads/order/' . $detail->gambar) }}"
                                                        alt="Gambar produk">
                                                </a>
                                            @else
                                                <span class="projectmp-thumb">
                                                    <img src="{{ asset('uploads/order/' . $detail->gambar) }}"
                                                        alt="Gambar produk">
                                                </span>
                                            @endif
                                        @elseif ($canAddOrderProduk)
                                            <a href="{{ route('orderDetail.gambar', $detail->id) }}"
                                                class="btn btn-sm btn-success text-white" title="Upload gambar">
                                                <i class='bx bx-image-alt'></i>
                                            </a>
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td class="text-nowrap small">
                                        {{ $detail->deathline ? \Carbon\Carbon::parse($detail->deathline)->format('d-m-Y') : '-' }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="10" class="text-center text-muted py-4">
                                        Belum ada produk tambahan
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="card projectmp-detail-card border-0 shadow-sm mb-3">
            <div class="card-header bg-white border-0 pt-3 px-3 px-md-4 pb-0">
                <h6 class="mb-0 fw-semibold">Notes</h6>
            </div>
            <div class="card-body px-3 px-md-4 pt-3">
                <form method="POST" action="{{ route('projectMp.chatStore', $projectMp->id) }}"
                    enctype="multipart/form-data" class="projectmp-detail-ajax-form"
                    data-reload-detail="{{ route('projectmp.detail', $projectMp->id) }}">
                    @csrf
                    <div class="input-group projectmp-chat-input mb-3">
                        <input type="text" class="form-control" placeholder="Tulis catatan..." name="isi">
                        <button class="btn btn-primary" type="submit" title="Kirim">
                            <i class='bx bx-send'></i>
                        </button>
                    </div>
                </form>

                <ul class="projectmp-chat-list list-unstyled mb-0">
                    @forelse ($chats as $chat)
                        @php
                            preg_match(
                                '/((?:[A-Za-z]:\\\\|https?:\/\/(?:drive|docs)\.google\.com\/)\S.*)/u',
                                $chat->isi ?? '',
                                $driveMatch,
                            );
                            $driveLink = $driveMatch[1] ?? null;
                        @endphp
                        <li class="projectmp-chat-item">
                            <div class="projectmp-chat-meta">
                                <span class="author">{{ $chat->author_name ?: 'Anonim' }}</span>
                                <span class="date">{{ date('d/m/Y', strtotime($chat->created_at)) }}</span>
                            </div>
                            <div class="projectmp-chat-bubble">
                                <span>{{ $chat->isi }}</span>
                                @if ($driveLink)
                                    <button type="button"
                                        class="btn btn-sm btn-outline-secondary copy-drive-link ms-1"
                                        data-copy-text="{{ $driveLink }}" title="Salin link">
                                        <i class='bx bx-copy'></i>
                                    </button>
                                @endif
                            </div>
                        </li>
                    @empty
                        <li class="text-muted small py-2">Belum ada catatan</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>
@endsection

@push('after-scripts')
    <style>
        .projectmp-detail-page {
            --pmp-border: #e6e8ec;
            --pmp-muted: #6c757d;
            --pmp-surface: #f7f8fa;
            --pmp-text: #212529;
        }

        .projectmp-detail-card {
            border-radius: 12px;
            overflow: hidden;
        }

        .projectmp-nota {
            font-size: 1.15rem;
            font-weight: 700;
            color: var(--pmp-text);
            word-break: break-word;
        }

        .projectmp-konsumen {
            font-size: 0.95rem;
        }

        .projectmp-mp-badge {
            font-weight: 600;
            letter-spacing: 0.02em;
            padding: 0.35rem 0.65rem;
        }

        .projectmp-pemproses-box {
            min-width: 10rem;
            background: var(--pmp-surface);
            border: 1px solid var(--pmp-border);
            border-radius: 10px;
            padding: 0.65rem 0.75rem;
        }

        .projectmp-summary-grid {
            display: grid;
            grid-template-columns: repeat(5, minmax(0, 1fr));
            gap: 0.65rem;
        }

        .projectmp-summary-item {
            background: var(--pmp-surface);
            border: 1px solid var(--pmp-border);
            border-radius: 10px;
            padding: 0.7rem 0.85rem;
            min-width: 0;
        }

        .projectmp-summary-item .label {
            display: block;
            font-size: 0.72rem;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: var(--pmp-muted);
            margin-bottom: 0.2rem;
        }

        .projectmp-summary-item .value {
            display: block;
            font-size: 1rem;
            font-weight: 700;
            color: var(--pmp-text);
            font-variant-numeric: tabular-nums;
        }

        .projectmp-summary-item.is-emphasis {
            background: #eef5ff;
            border-color: #cfe0ff;
        }

        .projectmp-summary-item.is-warn {
            background: #fff6e8;
            border-color: #f0d5a8;
        }

        .projectmp-keterangan {
            background: var(--pmp-surface);
            border: 1px dashed var(--pmp-border);
            border-radius: 10px;
            padding: 0.75rem 0.9rem;
        }

        .projectmp-table-wrap {
            border: 1px solid var(--pmp-border);
            border-radius: 10px;
            overflow: auto;
        }

        .projectmp-table {
            margin-bottom: 0;
        }

        .projectmp-table thead th {
            background: #f1f3f5;
            color: #495057;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.03em;
            font-weight: 600;
            white-space: nowrap;
            border-bottom-width: 1px;
            padding: 0.7rem 0.75rem;
        }

        .projectmp-table tbody td {
            padding: 0.75rem;
            vertical-align: middle;
            border-color: #edf0f2;
        }

        .projectmp-table tbody tr:hover {
            background: #fafbfc;
        }

        .projectmp-produk-cell {
            font-weight: 600;
            max-width: 280px;
            line-height: 1.35;
        }

        .projectmp-thumb {
            display: inline-block;
            line-height: 0;
        }

        .projectmp-thumb img {
            width: 64px;
            height: 64px;
            object-fit: cover;
            border-radius: 8px;
            border: 1px solid var(--pmp-border);
        }

        .projectmp-deadline-link {
            text-decoration: none;
            color: inherit;
            font-size: 0.9rem;
        }

        .projectmp-deadline-link:hover {
            color: var(--bs-primary, #0d6efd);
        }

        .projectmp-chat-input .form-control {
            border-radius: 999px 0 0 999px;
            border-color: var(--pmp-border);
        }

        .projectmp-chat-input .btn {
            border-radius: 0 999px 999px 0;
            padding-inline: 1rem;
        }

        .projectmp-chat-list {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }

        .projectmp-chat-meta {
            display: flex;
            justify-content: space-between;
            gap: 0.75rem;
            margin-bottom: 0.25rem;
            font-size: 0.8rem;
        }

        .projectmp-chat-meta .author {
            font-weight: 600;
            color: var(--bs-primary, #0d6efd);
        }

        .projectmp-chat-meta .date {
            color: var(--pmp-muted);
            white-space: nowrap;
        }

        .projectmp-chat-bubble {
            background: #f1f3f5;
            border-radius: 10px;
            padding: 0.7rem 0.9rem;
            line-height: 1.45;
            word-break: break-word;
        }

        .projectmp-chat-bubble .copy-drive-link {
            padding: 2px 6px;
            line-height: 1;
            vertical-align: middle;
        }

        @media (max-width: 991.98px) {
            .projectmp-summary-grid {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }
        }

        @media (max-width: 575.98px) {
            .projectmp-summary-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .projectmp-produk-cell {
                max-width: 180px;
            }
        }
    </style>
    <script>
        document.addEventListener('click', function(e) {
            var btn = e.target.closest('.copy-drive-link');
            if (!btn) return;

            var text = btn.getAttribute('data-copy-text') || '';
            var icon = btn.querySelector('i');

            function showCopied() {
                if (!icon) return;
                var originalClass = icon.className;
                icon.className = 'bx bx-check text-success';
                setTimeout(function() {
                    icon.className = originalClass;
                }, 1500);
            }

            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(text).then(showCopied);
            } else {
                var tempInput = document.createElement('textarea');
                tempInput.value = text;
                tempInput.style.position = 'fixed';
                tempInput.style.opacity = '0';
                document.body.appendChild(tempInput);
                tempInput.focus();
                tempInput.select();
                document.execCommand('copy');
                document.body.removeChild(tempInput);
                showCopied();
            }
        });
    </script>
@endpush
