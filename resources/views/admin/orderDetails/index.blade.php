@extends('layouts.app')

@section('title')
    Detail Order
@endsection

@section('content')
    @php
        $judulOrder = trim(
            ($order->nota ?? '') .
                ' | ' .
                ($order->kontak->nama ?? '') .
                ' - ' .
                ($order->konsumen_detail ?? ''),
        );
    @endphp

    <div class="order-detail-page">
        @include('layouts.includes.messages')

        <div class="card order-detail-card border-0 shadow-sm mb-3">
            <div class="card-body p-3 p-md-4">
                <div class="d-flex flex-wrap align-items-start justify-content-between gap-3 mb-3">
                    <div class="order-detail-heading min-w-0">
                        <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
                            <span class="order-nota">{{ $order->nota }}</span>
                            <button type="button" class="btn btn-sm btn-light border copy-order-title"
                                data-copy-text="{{ $judulOrder }}" title="Salin judul">
                                <i class='bx bx-copy'></i>
                            </button>
                        </div>
                        <div class="order-konsumen text-secondary">
                            <i class='bx bx-user'></i>
                            {{ $order->kontak->nama ?? '-' }}
                            @if (!empty($order->konsumen_detail))
                                <span class="text-muted">— {{ $order->konsumen_detail }}</span>
                            @endif
                        </div>
                        @if ($order->projectMp)
                            <div class="mt-2">
                                <a href="{{ route('projectmp.detail', $order->projectMp->id) }}"
                                    class="badge bg-primary text-decoration-none">
                                    <i class='bx bx-link-external'></i> ProjectMp: {{ $order->projectMp->nota }}
                                </a>
                            </div>
                        @endif
                    </div>

                    <div class="d-flex flex-wrap align-items-end justify-content-end gap-2">
                        <div class="order-pemproses-box">
                            <label class="form-label small text-secondary mb-1">Status</label>
                            <form action="{{ route('order.pemproses', $order->id) }}" method="post"
                                class="order-detail-ajax-form">
                                {{ csrf_field() }}
                                {{ method_field('patch') }}
                                <select class="form-select form-select-sm" aria-label="Pilih pemproses"
                                    name="pemproses_id" onchange="this.form.requestSubmit()">
                                    <option value="">- pilih -</option>
                                    @foreach (($pemprosesUtama ?? collect()) as $entry)
                                        <option value="{{ $entry->id }}"
                                            {{ $order->pemproses_id == $entry->id ? 'selected' : '' }}>
                                            {{ $entry->nama }}</option>
                                    @endforeach
                                </select>
                            </form>
                        </div>

                        @if ($canShowOrderActions)
                            <div class="d-flex flex-wrap gap-1">
                                <a href="{{ route('orderDetail.add', $order->id) }}"
                                    class="btn btn-success btn-sm rounded-pill text-white">
                                    <i class='bx bx-plus-circle'></i> tambah
                                </a>
                                <a href="{{ route('order.edit', $order->id) }}"
                                    class="btn btn-info btn-sm rounded-pill text-white">
                                    edit
                                </a>
                                <a href="{{ route('order.invoice', $order->id) }}"
                                    class="btn btn-primary btn-sm rounded-pill text-white">
                                    invoice
                                </a>
                            </div>
                        @endif
                    </div>
                </div>

                <div class="order-summary-grid">
                    <div class="order-summary-item">
                        <span class="label">Ongkir</span>
                        <span class="value">{{ number_format($order->ongkir, 0, ',', '.') }}</span>
                    </div>
                    <div class="order-summary-item">
                        <span class="label">Diskon</span>
                        <span class="value">{{ number_format($order->diskon, 0, ',', '.') }}</span>
                    </div>
                    <div class="order-summary-item is-emphasis">
                        <span class="label">Total</span>
                        <span class="value">{{ number_format($order->total, 0, ',', '.') }}</span>
                    </div>
                    <div class="order-summary-item">
                        <span class="label">Pembayaran</span>
                        <span class="value">{{ number_format($order->bayar, 0, ',', '.') }}</span>
                    </div>
                    <div class="order-summary-item {{ ($order->kekurangan ?? 0) > 0 ? 'is-warn' : '' }}">
                        <span class="label">Kekurangan</span>
                        <span class="value">{{ number_format($order->kekurangan, 0, ',', '.') }}</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="card order-detail-card border-0 shadow-sm mb-3">
            <div class="card-header bg-white border-0 pt-3 px-3 px-md-4 pb-0">
                <div class="d-flex align-items-center justify-content-between gap-2">
                    <h6 class="mb-0 fw-semibold">Produk Order</h6>
                    <span class="badge text-bg-light text-secondary border">{{ $orderDetails->count() }} item</span>
                </div>
            </div>
            <div class="card-body px-3 px-md-4 pt-3">
                <div class="table-responsive order-table-wrap">
                    <table class="table order-table align-middle mb-0" id="myTable">
                        <thead>
                            <tr>
                                <th>Produk</th>
                                <th>Tema</th>
                                <th class="text-center">Jml</th>
                                <th class="text-end">Harga</th>
                                <th class="text-end">Subtotal</th>
                                <th>Spesifikasi</th>
                                <th>Posisi</th>
                                <th>Pemproses</th>
                                <th class="text-center">Gambar</th>
                                <th>Deadline</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($orderDetails as $detail)
                                <tr>
                                    <td class="order-produk-cell">
                                        @if ($canEditLimited)
                                            <a class="order-produk-link"
                                                href="{{ route('orderDetail.edit', $detail->id) }}">
                                                {{ $detail->produk->namaLengkap }}
                                            </a>
                                        @else
                                            {{ $detail->produk->namaLengkap }}
                                        @endif
                                    </td>
                                    <td class="text-secondary small">{{ $detail->tema ?: '-' }}</td>
                                    <td class="text-center fw-semibold">{{ $detail->jumlah }}</td>
                                    <td class="text-end text-nowrap">{{ number_format($detail->harga) }}</td>
                                    <td class="text-end text-nowrap fw-semibold">
                                        {{ number_format($detail->harga * $detail->jumlah) }}
                                    </td>
                                    <td class="small text-secondary">
                                        @foreach ($detail->spek as $spek)
                                            <div>
                                                <span class="fw-semibold text-dark">{{ $spek->nama }}:</span>
                                                {{ $spek->pivot->keterangan }}
                                            </div>
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
                                        @if ($canEditLimited && !$isMarketingOnly && !$isProduksiLevel)
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
                                        @if ($canEditLimited && !$isMarketingOnly)
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
                                            @if ($canEditAll)
                                                <a href="{{ route('orderDetail.editGambar', $detail->id) }}"
                                                    class="order-thumb">
                                                    <img src="{{ asset('uploads/order/' . $detail->gambar) }}"
                                                        alt="Gambar produk">
                                                </a>
                                            @else
                                                <a href="#" class="order-detail-image-thumb order-thumb"
                                                    data-image-src="{{ asset('uploads/order/' . $detail->gambar) }}">
                                                    <img src="{{ asset('uploads/order/' . $detail->gambar) }}"
                                                        alt="Gambar produk">
                                                </a>
                                            @endif
                                        @elseif ($canEditAll)
                                            <a href="{{ route('orderDetail.gambar', $detail->id) }}"
                                                class="btn btn-sm btn-success text-white" title="Upload gambar">
                                                <i class='bx bx-image-alt'></i>
                                            </a>
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td class="text-nowrap small">
                                        {{ $detail->deathline ? date('d-m-Y', strtotime($detail->deathline)) : '-' }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="10" class="text-center text-muted py-4">Belum ada produk</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-lg-6">
                <div class="card order-detail-card border-0 shadow-sm h-100">
                    <div class="card-header bg-white border-0 pt-3 px-3 px-md-4 pb-0">
                        <h6 class="mb-0 fw-semibold">
                            <i class='bx bx-info-circle me-1 text-primary'></i> Informasi Pengiriman & Pembayaran
                        </h6>
                    </div>
                    <div class="card-body px-3 px-md-4 pt-3">
                        <div class="row g-3">
                            <div class="col-sm-6">
                                <div class="order-meta-item">
                                    <div class="order-meta-icon order-meta-icon--shipping">
                                        <i class='bx bx-package'></i>
                                    </div>
                                    <div class="order-meta-content">
                                        <span class="order-meta-label">Pengiriman</span>
                                        <p class="order-meta-value mb-0">{{ $order->pengiriman ?: '-' }}</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="order-meta-item">
                                    <div class="order-meta-icon order-meta-icon--invoice">
                                        <i class='bx bx-receipt'></i>
                                    </div>
                                    <div class="order-meta-content">
                                        <span class="order-meta-label">Invoice</span>
                                        <p class="order-meta-value mb-0">{{ $order->invoice ?: '-' }}</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="order-meta-item">
                                    <div class="order-meta-icon order-meta-icon--payment">
                                        <i class='bx bx-wallet'></i>
                                    </div>
                                    <div class="order-meta-content">
                                        <span class="order-meta-label">Pembayaran</span>
                                        <p class="order-meta-value mb-0">{{ $order->jenis_pembayaran ?: '-' }}</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="order-meta-item">
                                    <div class="order-meta-icon order-meta-icon--note">
                                        <i class='bx bx-note'></i>
                                    </div>
                                    <div class="order-meta-content">
                                        <span class="order-meta-label">Keterangan</span>
                                        <p class="order-meta-value mb-0">{{ $order->ket_kirim ?: '-' }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="card order-detail-card border-0 shadow-sm h-100">
                    <div class="card-header bg-white border-0 pt-3 px-3 px-md-4 pb-0">
                        <h6 class="mb-0 fw-semibold">
                            <i class='bx bx-message-dots me-1 text-primary'></i> Notes
                        </h6>
                    </div>
                    <div class="card-body px-3 px-md-4 pt-3">
                        <form method="POST" action="{{ route('order.chatStore', $order->id) }}"
                            enctype="multipart/form-data" class="order-detail-ajax-form"
                            data-reload-detail="{{ route('order.detail', $order->id) }}">
                            @csrf
                            <div class="input-group order-chat-input mb-3">
                                <input type="text" class="form-control" placeholder="Tulis catatan..." name="isi">
                                <button class="btn btn-primary" type="submit" title="Kirim">
                                    <i class='bx bx-send'></i>
                                </button>
                            </div>
                        </form>

                        <ul class="order-chat-list list-unstyled mb-0">
                            @forelse ($chats as $chat)
                                <li class="order-chat-item">
                                    <div class="order-chat-meta">
                                        <span class="author">{{ $chat->author_name ?: 'Anonim' }}</span>
                                        <span class="date">{{ date('d/m/Y', strtotime($chat->created_at)) }}</span>
                                    </div>
                                    <div class="order-chat-bubble">{{ $chat->isi }}</div>
                                </li>
                            @empty
                                <li class="text-muted small py-2">Belum ada catatan</li>
                            @endforelse
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('after-scripts')
    <style>
        .order-detail-page {
            --od-border: #e6e8ec;
            --od-muted: #6c757d;
            --od-surface: #f7f8fa;
            --od-text: #212529;
        }

        .order-detail-card {
            border-radius: 12px;
            overflow: hidden;
        }

        .order-nota {
            font-size: 1.15rem;
            font-weight: 700;
            color: var(--od-text);
            word-break: break-word;
        }

        .order-konsumen {
            font-size: 0.95rem;
        }

        .order-pemproses-box {
            min-width: 10rem;
            background: var(--od-surface);
            border: 1px solid var(--od-border);
            border-radius: 10px;
            padding: 0.65rem 0.75rem;
            text-align: left;
        }

        .order-summary-grid {
            display: grid;
            grid-template-columns: repeat(5, minmax(0, 1fr));
            gap: 0.65rem;
        }

        .order-summary-item {
            background: var(--od-surface);
            border: 1px solid var(--od-border);
            border-radius: 10px;
            padding: 0.7rem 0.85rem;
            min-width: 0;
        }

        .order-summary-item .label {
            display: block;
            font-size: 0.72rem;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: var(--od-muted);
            margin-bottom: 0.2rem;
        }

        .order-summary-item .value {
            display: block;
            font-size: 1rem;
            font-weight: 700;
            color: var(--od-text);
            font-variant-numeric: tabular-nums;
        }

        .order-summary-item.is-emphasis {
            background: #eef5ff;
            border-color: #cfe0ff;
        }

        .order-summary-item.is-warn {
            background: #fff6e8;
            border-color: #f0d5a8;
        }

        .order-table-wrap {
            border: 1px solid var(--od-border);
            border-radius: 10px;
            overflow: auto;
        }

        .order-table thead th {
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

        .order-table tbody td {
            padding: 0.75rem;
            vertical-align: middle;
            border-color: #edf0f2;
        }

        .order-table tbody tr:hover {
            background: #fafbfc;
        }

        .order-produk-cell {
            font-weight: 600;
            max-width: 260px;
            line-height: 1.35;
        }

        .order-produk-link {
            text-decoration: none;
            color: inherit;
        }

        .order-produk-link:hover {
            color: var(--bs-primary, #0d6efd);
        }

        .order-thumb {
            display: inline-block;
            line-height: 0;
        }

        .order-thumb img {
            width: 64px;
            height: 64px;
            object-fit: cover;
            border-radius: 8px;
            border: 1px solid var(--od-border);
        }

        .order-meta-item {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            height: 100%;
            padding: 14px;
            background: var(--od-surface);
            border: 1px solid var(--od-border);
            border-radius: 10px;
        }

        .order-meta-icon {
            flex-shrink: 0;
            width: 42px;
            height: 42px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 10px;
            font-size: 1.25rem;
        }

        .order-meta-icon--shipping {
            background: #e7f1ff;
            color: #0d6efd;
        }

        .order-meta-icon--invoice {
            background: #e8f7ee;
            color: #198754;
        }

        .order-meta-icon--payment {
            background: #fff3cd;
            color: #997404;
        }

        .order-meta-icon--note {
            background: #f3e8ff;
            color: #6f42c1;
        }

        .order-meta-label {
            display: block;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: var(--od-muted);
            margin-bottom: 4px;
        }

        .order-meta-value {
            font-size: 0.95rem;
            font-weight: 600;
            color: var(--od-text);
            word-break: break-word;
        }

        .order-chat-input .form-control {
            border-radius: 999px 0 0 999px;
            border-color: var(--od-border);
        }

        .order-chat-input .btn {
            border-radius: 0 999px 999px 0;
            padding-inline: 1rem;
        }

        .order-chat-list {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
            max-height: 320px;
            overflow-y: auto;
        }

        .order-chat-meta {
            display: flex;
            justify-content: space-between;
            gap: 0.75rem;
            margin-bottom: 0.25rem;
            font-size: 0.8rem;
        }

        .order-chat-meta .author {
            font-weight: 600;
            color: var(--bs-primary, #0d6efd);
        }

        .order-chat-meta .date {
            color: var(--od-muted);
            white-space: nowrap;
        }

        .order-chat-bubble {
            background: #f1f3f5;
            border-radius: 10px;
            padding: 0.7rem 0.9rem;
            line-height: 1.45;
            word-break: break-word;
        }

        @media (max-width: 991.98px) {
            .order-summary-grid {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }
        }

        @media (max-width: 575.98px) {
            .order-summary-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .order-produk-cell {
                max-width: 180px;
            }
        }
    </style>
    <script>
        document.addEventListener('click', function(e) {
            var btn = e.target.closest('.copy-order-title');
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
