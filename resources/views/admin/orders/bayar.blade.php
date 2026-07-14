@extends('layouts.app')

@section('title')
    Bayar Orders
@endsection

@section('content')
    <div class="card">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="card-title">add bayar</h5>
                </div>
                <a href="{{ route('order.dashboard') }}" class="btn btn-success ">back</a>
            </div>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('order.storeBayar') }}" enctype="multipart/form-data" onsubmit="return validateBayar(event)">
                @csrf
                <input type="hidden" name="order_id" value="{{ $order->id }}">
                <div class="form-group mb-3">
                    <label for="tema">konsumen</label>
                    <input class="form-control" type="text" value="{{ $order->kontak->nama }}" disabled>
                </div>
                <div class="form-group mb-3">
                    <label for="tema">total tagihan</label>
                    <input id="total" class="form-control" type="text" value="{{ $order->total }}" disabled>
                </div>
                <div class="form-group mb-3">
                    <label for="tema">pembayaran</label>
                    <input id="pembayaran" class="form-control" type="text" value="{{ $order->bayar }}" disabled>
                </div>
                <div class="form-group mb-3">
                    <label for="tema">kekurangan</label>
                    <input id="kekurangan" class="form-control" type="text" value="{{ $order->kekurangan }}" disabled>
                </div>
                <div class="form-group mb-3">
                    <label for="diskon">diskon</label>
                    <input id="diskon" oninput="updateSubTotal()" class="form-control {{ $errors->has('diskon') ? 'is-invalid' : '' }}" type="number"
                        name="diskon" min="0" max="{{ $order->kekurangan }}" value="{{ old('diskon', 0) }}">
                    @if ($errors->has('diskon'))
                        <div class="invalid-feedback">
                            {{ $errors->first('diskon') }}
                        </div>
                    @endif
                </div>
                <div class="form-group mb-3">
                    <label for="jumlah">Jumlah</label>
                    <input id="jumlah" oninput="validateJumlah()" class="form-control {{ $errors->has('jumlah') ? 'is-invalid' : '' }}" type="number"
                        name="jumlah" min="1" max="{{ $order->kekurangan }}" value="{{ old('jumlah', $order->kekurangan) }}">
                    <div id="jumlah-error" class="invalid-feedback"></div>
                    @if ($errors->has('jumlah'))
                        <div class="invalid-feedback d-block">
                            {{ $errors->first('jumlah') }}
                        </div>
                    @endif
                </div>
                <div class="form-group mb-3">
                    <label for="akun_detail_id">kas</label>
                    <select class="form-select {{ $errors->has('akun_detail_id') ? 'is-invalid' : '' }}"
                        aria-label="Default select example" name="akun_detail_id" id="akun_detail_id">
                        <option >-- pilih kas --</option>
                        @foreach ($kas as $id => $entry)
                            <option value="{{ $id }}">{{ $entry }}</option>
                        @endforeach
                    </select>
                    @if ($errors->has('akun_detail_id'))
                        <div class="invalid-feedback">
                            {{ $errors->first('akun_detail_id') }}
                        </div>
                    @endif
                </div>
                <div class="form-group mb-3">
                    <label for="tanggal">tanggal</label>
                    <input class="form-control {{ $errors->has('tanggal') ? 'is-invalid' : '' }}" type="date"
                        name="tanggal" id="tanggal" value="{{ old('tanggal', date('Y-m-d')) }}">
                    @if ($errors->has('tanggal'))
                        <div class="invalid-feedback">
                            {{ $errors->first('tanggal') }}
                        </div>
                    @endif
                </div>
                <div class="form-group mb-3">
                    <label for="ket">ket</label>
                    <input id="ket" class="form-control" type="text"
                        name="ket" id="ket">
                </div>
                <div class="form-group">
                    <button class="btn btn-primary mt-4" type="submit">
                        save
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('after-scripts')
    <script>
        const orderTotalAwal = {{ $order->total + $order->diskon }};
        const orderBayar = {{ $order->bayar }};

        function getMaxJumlah() {
            const diskon = parseFloat(document.getElementById('diskon').value) || 0;
            const totalSetelahDiskon = orderTotalAwal - diskon;
            return Math.max(0, totalSetelahDiskon - orderBayar);
        }

        function showJumlahError(message) {
            const errorEl = document.getElementById('jumlah-error');
            const jumlah = document.getElementById('jumlah');
            if (message) {
                errorEl.textContent = message;
                errorEl.classList.add('d-block');
                jumlah.classList.add('is-invalid');
            } else {
                errorEl.textContent = '';
                errorEl.classList.remove('d-block');
                jumlah.classList.remove('is-invalid');
            }
        }

        function validateJumlah() {
            const jumlah = document.getElementById('jumlah');
            const value = parseFloat(jumlah.value) || 0;
            const maxJumlah = getMaxJumlah();

            jumlah.max = maxJumlah;

            if (value > maxJumlah) {
                showJumlahError('Jumlah pembayaran tidak boleh melebihi total order (maksimal ' + maxJumlah + ').');
                return false;
            }

            if (value < 1) {
                showJumlahError('Jumlah pembayaran minimal 1.');
                return false;
            }

            showJumlahError('');
            return true;
        }

        function updateSubTotal() {
            const total = document.getElementById('total');
            const diskon = document.getElementById('diskon');
            const jumlah = document.getElementById('jumlah');
            const kekurangan = document.getElementById('kekurangan');
            const diskonValue = parseFloat(diskon.value) || 0;
            const maxJumlah = getMaxJumlah();

            total.value = orderTotalAwal - diskonValue;
            kekurangan.value = maxJumlah;
            jumlah.max = maxJumlah;
            jumlah.value = maxJumlah;
            validateJumlah();
        }

        function validateBayar(event) {
            if (!validateJumlah()) {
                event.preventDefault();
                return false;
            }
            return true;
        }
    </script>
@endpush
