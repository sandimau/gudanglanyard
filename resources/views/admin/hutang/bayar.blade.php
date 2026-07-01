@extends('layouts.app')

@section('content')
    @php
        $isKeluarKas = in_array($hutang->jenis, ['hutang', 'belanja', 'belanja produksi']);
    @endphp

    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-1">Pembayaran {{ ucfirst($hutang->jenis) }}</h5>
            <small class="text-muted d-none">{{ $hutang->kontak->nama }} &bull; {{ $hutang->tanggal->format('d/m/Y') }}</small>
        </div>

        <div class="card-body">
            @if (session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
                <span class="text-muted">{{ $hutang->kontak->nama }} &bull; {{ $hutang->tanggal->format('d/m/Y') }}</span>
                @if ($hutang->sisa <= 0)
                    <span class="badge bg-success">Lunas</span>
                @else
                    <span class="badge bg-warning text-dark">Belum Lunas</span>
                @endif
            </div>

            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <div class="border rounded p-3 h-100 bg-light">
                        <div class="text-muted small">Total {{ ucfirst($hutang->jenis) }}</div>
                        <div class="fs-5 fw-semibold mb-0">
                            Rp {{ number_format($hutang->jumlah, 0, ',', '.') }}
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="border rounded p-3 h-100 bg-light">
                        <div class="text-muted small">Total Bayar</div>
                        <div class="fs-5 fw-semibold text-success mb-0">
                            Rp {{ number_format($hutang->total_bayar, 0, ',', '.') }}
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="border rounded p-3 h-100 {{ $hutang->sisa > 0 ? 'border-warning bg-warning bg-opacity-10' : 'border-success bg-success bg-opacity-10' }}">
                        <div class="text-muted small">Sisa</div>
                        <div class="fs-5 fw-semibold mb-0 {{ $hutang->sisa > 0 ? 'text-warning' : 'text-success' }}">
                            Rp {{ number_format($hutang->sisa, 0, ',', '.') }}
                        </div>
                    </div>
                </div>
            </div>

            @if ($hutang->sisa > 0)
                <div class="card border mb-4">
                    <div class="card-header bg-white py-3">
                        <h6 class="mb-0">Input Pembayaran</h6>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('hutang.bayarStore') }}" method="POST">
                            @csrf
                            <input type="hidden" name="hutang_id" value="{{ $hutang->id }}">
                            <input type="hidden" name="jenis" value="{{ $hutang->jenis }}">

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="akun_detail_id" class="form-label">
                                        {{ $isKeluarKas ? 'Keluar dari Kas' : 'Masuk ke Kas' }}
                                    </label>
                                    <select name="akun_detail_id" id="akun_detail_id"
                                        class="form-select @error('akun_detail_id') is-invalid @enderror" required>
                                        <option value="">Pilih kas</option>
                                        @foreach ($kas as $k)
                                            <option value="{{ $k->id }}" {{ old('akun_detail_id') == $k->id ? 'selected' : '' }}>
                                                {{ $k->nama }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('akun_detail_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-6">
                                    <label for="tanggal" class="form-label">Tanggal</label>
                                    <input type="date" name="tanggal" id="tanggal"
                                        class="form-control @error('tanggal') is-invalid @enderror"
                                        value="{{ old('tanggal', date('Y-m-d')) }}" required>
                                    @error('tanggal')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-6">
                                    <label for="jumlah" class="form-label">Jumlah</label>
                                    <div class="input-group">
                                        <span class="input-group-text">Rp</span>
                                        <input type="number" name="jumlah" id="jumlah"
                                            class="form-control @error('jumlah') is-invalid @enderror"
                                            value="{{ old('jumlah', $hutang->sisa) }}" max="{{ $hutang->sisa }}"
                                            min="1" required>
                                    </div>
                                    <div class="form-text">Maksimal Rp {{ number_format($hutang->sisa, 0, ',', '.') }}</div>
                                    @error('jumlah')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-12">
                                    <label for="keterangan" class="form-label">Keterangan</label>
                                    <textarea name="keterangan" id="keterangan" rows="3"
                                        class="form-control @error('keterangan') is-invalid @enderror"
                                        placeholder="Keterangan pembayaran" required>{{ old('keterangan') }}</textarea>
                                    @error('keterangan')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-12">
                                    <button type="submit" class="btn btn-primary">Simpan Pembayaran</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            @endif

            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="mb-0">Riwayat Pembayaran</h6>
                <span class="badge bg-secondary">{{ $hutang->details->count() }} transaksi</span>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered table-striped mb-0">
                    <thead class="table-light">
                        <tr>
                            <th width="50">No</th>
                            <th>Tanggal</th>
                            <th>Jumlah</th>
                            <th>Kas</th>
                            <th>Keterangan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($hutang->details as $detail)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ $detail->tanggal->format('d/m/Y') }}</td>
                                <td class="fw-semibold">Rp {{ number_format($detail->jumlah, 0, ',', '.') }}</td>
                                <td>{{ $detail->akun_detail->nama ?? '-' }}</td>
                                <td>{{ $detail->keterangan ?: '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">
                                    Belum ada pembayaran
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
