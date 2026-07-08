@extends('layouts.app')

@section('title')
    Detail Order
@endsection

@section('content')
    <div class="bg-light rounded">
        @include('layouts.includes.messages')
        <div class="row">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-header">
                        <div class="d-flex justify-content-between align-details-center">
                            <div>
                                <h5 class="card-title">{{ $order->nota }} | {{ $order->kontak->nama }} - {{ $order->konsumen_detail }}</h5>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-8">
                                <div class="row">
                                    <div class="col-lg-2 col-sm-4">
                                        <h6 class="mb-0 text-secondary">Ongkir</h6>
                                        <p>{{ number_format($order->ongkir, 0, ',', '.') }}</p>
                                    </div>
                                    <div class="col-lg-2 col-sm-4">
                                        <h6 class="mb-0 text-secondary">Diskon</h6>
                                        <p>{{ number_format($order->diskon, 0, ',', '.') }}</p>
                                    </div>
                                    <div class="col-lg-2 col-sm-4">
                                        <h6 class="mb-0 text-secondary">Total</h6>
                                        <p>{{ number_format($order->total, 0, ',', '.') }}</p>
                                    </div>
                                    <div class="col-lg-2 col-sm-4">
                                        <h6 class="mb-0 text-secondary">Pembayaran</h6>
                                        <p>{{ number_format($order->bayar, 0, ',', '.') }}</p>
                                    </div>
                                    <div class="col-lg-2 col-sm-4">
                                        <h6 class="mb-0 text-secondary">Kekurangan</h6>
                                        <p>{{ number_format($order->kekurangan, 0, ',', '.') }}</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4 text-end">
                                @if ($canShowOrderActions)
                                    <a href="{{ route('orderDetail.add', $order->id) }}"
                                        class="btn btn-success rounded-pill text-white">
                                        <i class='bx bx-plus-circle'></i> tambah
                                    </a>
                                    <a href="{{ route('order.edit', $order->id) }}"
                                        class="btn btn-info rounded-pill text-white">
                                        edit
                                    </a>
                                    <a href="{{ route('order.invoice', $order->id) }}"
                                        class="btn btn-primary rounded-pill text-white">
                                        invoice
                                    </a>
                                @endif
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-striped" id="myTable">
                                <thead>
                                    <tr>
                                        <th>produk</th>
                                        <th>tema</th>
                                        <th>jml</th>
                                        <th>harga</th>
                                        <th>subtotal</th>
                                        <th>spesifikasi</th>
                                        <th>status</th>
                                        <th>pemproses</th>
                                        <th>gambar</th>
                                        <th>deadline</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($orderDetails as $detail)
                                        <tr>
                                            <td style="font-weight: 600;">
                                                @if ($canEditLimited)
                                                    <a style="text-decoration:none"
                                                        href="{{ route('orderDetail.edit', $detail->id) }}">{{ $detail->produk->namaLengkap }}</a>
                                                @else
                                                    {{ $detail->produk->namaLengkap }}
                                                @endif
                                            </td>
                                            <td>{{ $detail->tema }}</td>
                                            <td>{{ $detail->jumlah }}</td>
                                            <td>{{ number_format($detail->harga) }}</td>
                                            <td>{{ number_format($detail->harga * $detail->jumlah) }}</td>
                                            <td>
                                                @foreach ($detail->spek as $spek)
                                                    <span style="font-weight: 600"> {{ $spek->nama }}: </span>
                                                    {{ $spek->pivot->keterangan }},
                                                @endforeach

                                                @if (!empty($detail->keterangan))
                                                    <span class='text-danger'> keterangan:</span>
                                                    {{ $detail->keterangan }}
                                                @endif
                                            </td>
                                            <td>
                                                @if ($canEditLimited && ! $isMarketingOnly && ! $isProduksiLevel)
                                                    <form action="{{ route('orderDetail.status', $detail->id) }}"
                                                        method="post" class="order-detail-ajax-form">
                                                        {{ csrf_field() }}
                                                        {{ method_field('patch') }}
                                                        <select class="form-select" aria-label="Default select example"
                                                            name="produksi_id" onchange="this.form.requestSubmit()">
                                                            @foreach (\App\Models\Produksi::statusPathForDetail($detail) as $entry)
                                                                <option value="{{ $entry->id }}"
                                                                    {{ $detail->produksi_id == $entry->id ? 'selected' : '' }}>
                                                                    {{ $entry->nama }}</option>
                                                            @endforeach
                                                        </select>
                                                    </form>
                                                @else
                                                    {{ $detail->produksi->nama ?? '-' }}
                                                @endif
                                            </td>
                                            <td>
                                                @if ($canEditLimited && ! $isMarketingOnly)
                                                    <form action="{{ route('orderDetail.pemproses', $detail->id) }}"
                                                        method="post" class="order-detail-ajax-form">
                                                        {{ csrf_field() }}
                                                        {{ method_field('patch') }}
                                                        <select class="form-select" aria-label="Pilih pemproses"
                                                            name="pemproses_id" onchange="this.form.requestSubmit()">
                                                            <option value="">- pilih -</option>
                                                            @foreach (($pemproses ?? collect()) as $entry)
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
                                            <td>
                                                @if ($detail->gambar)
                                                    @if ($canEditAll)
                                                        <a href="{{ route('orderDetail.editGambar', $detail->id) }}">
                                                            <img style="width: 100px"
                                                                src="{{ asset('uploads/order/' . $detail->gambar) }}"
                                                                alt="">
                                                        </a>
                                                    @else
                                                        <a href="#"
                                                            class="order-detail-image-thumb"
                                                            data-image-src="{{ asset('uploads/order/' . $detail->gambar) }}">
                                                            <img style="width: 100px"
                                                                src="{{ asset('uploads/order/' . $detail->gambar) }}"
                                                                alt="">
                                                        </a>
                                                    @endif
                                                @elseif ($canEditAll)
                                                    <a href="{{ route('orderDetail.gambar', $detail->id) }}"
                                                        class="btn btn-success text-white"><i
                                                            class='bx bx-image-alt'></i></a>
                                                @else
                                                    -
                                                @endif
                                            </td>
                                            <td>
                                                {{ date('d-m-Y', strtotime($detail->deathline)) }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="card mt-4 order-info-card shadow-sm">
                    <div class="card-header bg-white py-3">
                        <h6 class="mb-0 fw-semibold">
                            <i class='bx bx-info-circle me-1 text-primary'></i> Informasi Pengiriman & Pembayaran
                        </h6>
                    </div>
                    <div class="card-body pt-2">
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
                <div class="card mt-4 order-notes-card shadow-sm">
                    <div class="card-header bg-white py-3">
                        <h6 class="mb-0 fw-semibold">
                            <i class='bx bx-message-dots me-1 text-primary'></i> Notes
                        </h6>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('order.chatStore', $order->id) }}"
                            enctype="multipart/form-data" class="order-detail-ajax-form"
                            data-reload-detail="{{ route('order.detail', $order->id) }}">
                            @csrf
                            <div class="input-group mb-3">
                                <input type="text" class="form-control chat" placeholder="tulis pesan" name="isi">
                                <button class="input-group-text btn btn-primary rounded-pill" type="submit"><i
                                        class='bx bx-send'></i></button>
                            </div>
                        </form>
                        <div class="iframe">
                            <small>
                                <ul class="chat-list p-0 m-0">
                                    @foreach ($chats as $chat)
                                        <li class="d-flex justify-content-between align-items-end pt-2">
                                            <div class="chat-content">
                                                @if ($chat->author_name)
                                                    <div class="text-primary"><b>{{ $chat->author_name }}</b></div>
                                                @endif
                                                <div class="box">{{ $chat->isi }}</div>
                                            </div>
                                            <div class="ps-2">{{ date('d/m/Y', strtotime($chat->created_at)) }}</div>
                                        </li>
                                    @endforeach
                                </ul>
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('after-scripts')
    <style>
        .order-info-card,
        .order-notes-card {
            border: 1px solid #e9ecef;
            border-radius: 12px;
            overflow: hidden;
        }

        .order-meta-item {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            height: 100%;
            padding: 14px;
            background: #f8f9fa;
            border: 1px solid #eef1f4;
            border-radius: 10px;
            transition: box-shadow 0.2s ease, transform 0.2s ease;
        }

        .order-meta-item:hover {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.06);
            transform: translateY(-1px);
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
            color: #6c757d;
            margin-bottom: 4px;
        }

        .order-meta-value {
            font-size: 0.95rem;
            font-weight: 600;
            color: #212529;
            word-break: break-word;
        }

        .chat {
            border: none;
            border-bottom: solid #7c7c7c 1px
        }

        .chat:focus {
            box-shadow: none
        }

        .iframe {
            padding: 0px 10px;
        }

        .iframe ul {
            list-style: none;
        }

        .iframe .chat-content .box {
            padding: 10px 20px 10px 10px;
            background-color: #dddddd;
            border-radius: 5px;
        }
    </style>
@endpush
