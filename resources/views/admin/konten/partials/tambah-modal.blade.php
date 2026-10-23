@php $gagalTambah = old('_konten_popup'); @endphp
<div class="modal fade" id="tambahKontenModal" tabindex="-1" aria-labelledby="tambahKontenModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <form method="POST" action="{{ route('konten.store') }}">
                @csrf
                <input type="hidden" name="asal" value="{{ $modalAsal }}">
                <input type="hidden" name="_konten_popup" value="1">
                <div class="modal-header">
                    <h5 class="modal-title" id="tambahKontenModalLabel">Tambah Konten</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label" for="popup-tanggal">Tanggal</label>
                            <input type="date" id="popup-tanggal" name="tanggal" required
                                class="form-control {{ $gagalTambah && $errors->has('tanggal') ? 'is-invalid' : '' }}"
                                value="{{ $gagalTambah ? old('tanggal') : $modalTanggalDefault }}">
                            @if ($gagalTambah) @error('tanggal') <div class="invalid-feedback">{{ $message }}</div> @enderror @endif
                        </div>
                        <div class="col-md-8">
                            <label class="form-label" for="popup-topik">Topik</label>
                            <input type="text" id="popup-topik" name="topik" required maxlength="255"
                                class="form-control {{ $gagalTambah && $errors->has('topik') ? 'is-invalid' : '' }}" value="{{ $gagalTambah ? old('topik') : '' }}">
                            @if ($gagalTambah) @error('topik') <div class="invalid-feedback">{{ $message }}</div> @enderror @endif
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="popup-platform">Platform</label>
                            <input type="text" id="popup-platform" name="platform" required maxlength="255"
                                placeholder="Contoh: Instagram, TikTok" class="form-control {{ $gagalTambah && $errors->has('platform') ? 'is-invalid' : '' }}"
                                value="{{ $gagalTambah ? old('platform') : '' }}">
                            @if ($gagalTambah) @error('platform') <div class="invalid-feedback">{{ $message }}</div> @enderror @endif
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="popup-waktu-posting">Waktu Posting</label>
                            <input type="time" id="popup-waktu-posting" name="waktu_posting" required
                                class="form-control {{ $gagalTambah && $errors->has('waktu_posting') ? 'is-invalid' : '' }}" value="{{ $gagalTambah ? old('waktu_posting') : '' }}">
                            @if ($gagalTambah) @error('waktu_posting') <div class="invalid-feedback">{{ $message }}</div> @enderror @endif
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="popup-tujuan">Tujuan</label>
                            <textarea id="popup-tujuan" name="tujuan" rows="4" required
                                class="form-control {{ $gagalTambah && $errors->has('tujuan') ? 'is-invalid' : '' }}">{{ $gagalTambah ? old('tujuan') : '' }}</textarea>
                            @if ($gagalTambah) @error('tujuan') <div class="invalid-feedback">{{ $message }}</div> @enderror @endif
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan Konten</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('after-scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const modal = document.getElementById('tambahKontenModal');
            const tanggal = document.getElementById('popup-tanggal');

            modal.addEventListener('show.bs.modal', function (event) {
                if (event.relatedTarget?.dataset.tanggal) {
                    tanggal.value = event.relatedTarget.dataset.tanggal;
                }
            });

            @if ($errors->any() && old('_konten_popup'))
                bootstrap.Modal.getOrCreateInstance(modal).show();
            @endif
        });
    </script>
@endpush
