@php
    $editFailedValues = old('_konten_edit_popup') ? [
        'kontenId' => old('konten_id'),
        'tanggal' => old('tanggal'),
        'topik' => old('topik'),
        'platform' => old('platform'),
        'waktuPosting' => old('waktu_posting'),
        'tujuan' => old('tujuan'),
    ] : null;
@endphp

<div class="modal fade" id="editKontenModal" tabindex="-1" aria-labelledby="editKontenModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editKontenModalLabel">Edit Konten</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <form id="editKontenForm" method="POST" action="">
                @csrf @method('PATCH')
                <input type="hidden" name="asal" value="{{ $modalAsal ?? 'jadwal' }}">
                <input type="hidden" name="daftar_bulan" value="{{ $modalBulan ?? '' }}">
                <input type="hidden" name="daftar_q" value="{{ $modalPencarian ?? '' }}">
                <input type="hidden" name="daftar_page" value="{{ $modalHalaman ?? '' }}">
                <input type="hidden" name="_konten_edit_popup" value="1">
                <input type="hidden" name="konten_id" id="edit-konten-id">
            </form>
            <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label" for="edit-konten-tanggal">Tanggal</label>
                            <input type="date" id="edit-konten-tanggal" name="tanggal" form="editKontenForm" required
                                class="form-control {{ $editFailedValues && $errors->has('tanggal') ? 'is-invalid' : '' }}">
                            @if ($editFailedValues) @error('tanggal') <div class="invalid-feedback">{{ $message }}</div> @enderror @endif
                        </div>
                        <div class="col-md-8">
                            <label class="form-label" for="edit-konten-topik">Topik</label>
                            <input type="text" id="edit-konten-topik" name="topik" form="editKontenForm" required maxlength="255"
                                class="form-control {{ $editFailedValues && $errors->has('topik') ? 'is-invalid' : '' }}">
                            @if ($editFailedValues) @error('topik') <div class="invalid-feedback">{{ $message }}</div> @enderror @endif
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="edit-konten-platform">Platform</label>
                            <input type="text" id="edit-konten-platform" name="platform" form="editKontenForm" required maxlength="255"
                                class="form-control {{ $editFailedValues && $errors->has('platform') ? 'is-invalid' : '' }}">
                            @if ($editFailedValues) @error('platform') <div class="invalid-feedback">{{ $message }}</div> @enderror @endif
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="edit-konten-waktu-posting">Waktu Posting</label>
                            <input type="time" id="edit-konten-waktu-posting" name="waktu_posting" form="editKontenForm" required
                                class="form-control {{ $editFailedValues && $errors->has('waktu_posting') ? 'is-invalid' : '' }}">
                            @if ($editFailedValues) @error('waktu_posting') <div class="invalid-feedback">{{ $message }}</div> @enderror @endif
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="edit-konten-tujuan">Tujuan</label>
                            <textarea id="edit-konten-tujuan" name="tujuan" form="editKontenForm" rows="4" required
                                class="form-control {{ $editFailedValues && $errors->has('tujuan') ? 'is-invalid' : '' }}"></textarea>
                            @if ($editFailedValues) @error('tujuan') <div class="invalid-feedback">{{ $message }}</div> @enderror @endif
                        </div>
                    </div>
                <section class="konten-chat mt-4 pt-3 border-top" aria-label="Chat konten">
                    <h6 class="fw-semibold mb-3"><i class="bx bx-message-dots me-1"></i> Chat</h6>
                    <ul id="kontenChatList" class="konten-chat__list list-unstyled mb-3" aria-live="polite">
                        <li class="text-muted small">Memuat chat...</li>
                    </ul>
                    <form id="kontenChatForm" method="POST" action="">
                        @csrf
                        <label class="visually-hidden" for="konten-chat-isi">Tulis pesan</label>
                        <div class="input-group">
                            <input type="text" id="konten-chat-isi" name="isi" class="form-control"
                                maxlength="5000" placeholder="Tulis pesan..." required>
                            <button type="submit" class="btn btn-primary" title="Kirim pesan" aria-label="Kirim pesan">
                                <i class="bx bx-send" aria-hidden="true"></i>
                            </button>
                        </div>
                        <p id="kontenChatError" class="text-danger small mt-2 mb-0" role="alert" hidden></p>
                    </form>
                </section>
            </div>
            <div class="modal-footer d-flex justify-content-between">
                <form id="hapusKontenForm" method="POST" action="" onsubmit="return confirm('Hapus jadwal konten ini?')">
                    @csrf @method('DELETE')
                    <input type="hidden" name="asal" value="{{ $modalAsal ?? 'jadwal' }}">
                    <input type="hidden" name="daftar_bulan" value="{{ $modalBulan ?? '' }}">
                    <input type="hidden" name="daftar_q" value="{{ $modalPencarian ?? '' }}">
                    <input type="hidden" name="daftar_page" value="{{ $modalHalaman ?? '' }}">
                    <button type="submit" class="btn btn-outline-danger">Hapus</button>
                </form>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" form="editKontenForm" class="btn btn-primary">Simpan Perubahan</button>
                </div>
            </div>
        </div>
    </div>
</div>

@push('after-scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const modal = document.getElementById('editKontenModal');
            const form = document.getElementById('editKontenForm');
            const hapusForm = document.getElementById('hapusKontenForm');
            const chatForm = document.getElementById('kontenChatForm');
            const chatList = document.getElementById('kontenChatList');
            const chatInput = document.getElementById('konten-chat-isi');
            const chatError = document.getElementById('kontenChatError');
            const chatButton = chatForm.querySelector('button[type="submit"]');
            const gagalEdit = @json($editFailedValues);
            let chatRequestId = 0;
            const kolom = {
                tanggal: document.getElementById('edit-konten-tanggal'),
                topik: document.getElementById('edit-konten-topik'),
                platform: document.getElementById('edit-konten-platform'),
                waktuPosting: document.getElementById('edit-konten-waktu-posting'),
                tujuan: document.getElementById('edit-konten-tujuan')
            };

            function chatMessage(message) {
                const item = document.createElement('li');
                item.className = 'konten-chat__item';
                const meta = document.createElement('div');
                meta.className = 'konten-chat__meta';
                const author = document.createElement('span');
                author.className = 'fw-semibold text-primary';
                author.textContent = message.author;
                const date = document.createElement('span');
                date.className = 'text-muted';
                date.textContent = message.date;
                const bubble = document.createElement('div');
                bubble.className = 'konten-chat__bubble';
                bubble.textContent = message.text;
                meta.append(author, date);
                item.append(meta, bubble);
                return item;
            }

            function showChatInfo(message) {
                const item = document.createElement('li');
                item.className = 'text-muted small py-2';
                item.textContent = message;
                chatList.replaceChildren(item);
            }

            modal.addEventListener('show.bs.modal', function (event) {
                const tombol = event.relatedTarget;
                if (!tombol?.dataset.kontenId) return;

                form.action = tombol.dataset.action;
                hapusForm.action = tombol.dataset.deleteAction;
                chatForm.action = tombol.dataset.chatStoreUrl;
                document.getElementById('edit-konten-id').value = tombol.dataset.kontenId;
                const data = gagalEdit && String(gagalEdit.kontenId) === tombol.dataset.kontenId
                    ? gagalEdit : tombol.dataset;
                Object.keys(kolom).forEach(function (key) {
                    kolom[key].value = data[key] ?? '';
                });

                const tampilkanError = data === gagalEdit;
                modal.querySelectorAll('.is-invalid').forEach(function (input) {
                    input.classList.toggle('is-invalid', tampilkanError);
                });
                modal.querySelectorAll('.invalid-feedback').forEach(function (pesan) {
                    pesan.style.display = tampilkanError ? '' : 'none';
                });

                chatInput.value = '';
                chatError.hidden = true;
                chatButton.disabled = true;
                showChatInfo('Memuat chat...');
                const requestId = ++chatRequestId;
                fetch(tombol.dataset.chatUrl, { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' })
                    .then(function (response) {
                        if (!response.ok) throw new Error('Gagal memuat chat.');
                        return response.json();
                    })
                    .then(function (data) {
                        if (requestId !== chatRequestId) return;
                        chatList.replaceChildren();
                        if (!data.chats.length) return showChatInfo('Belum ada pesan.');
                        data.chats.forEach(function (message) { chatList.appendChild(chatMessage(message)); });
                        chatList.scrollTop = chatList.scrollHeight;
                    })
                    .catch(function () {
                        if (requestId === chatRequestId) showChatInfo('Gagal memuat chat. Buka kembali popup untuk mencoba lagi.');
                    }).finally(function () {
                        if (requestId === chatRequestId) chatButton.disabled = false;
                    });
            });

            modal.addEventListener('hidden.bs.modal', function () { chatRequestId++; });

            chatForm.addEventListener('submit', function (event) {
                event.preventDefault();
                if (!chatInput.value.trim()) return;
                const requestId = chatRequestId;
                chatButton.disabled = true;
                chatError.hidden = true;

                fetch(chatForm.action, {
                    method: 'POST',
                    body: new FormData(chatForm),
                    headers: { 'Accept': 'application/json' },
                    credentials: 'same-origin'
                }).then(function (response) {
                    return response.json().then(function (data) {
                        if (!response.ok) throw new Error(data.errors?.isi?.[0] || 'Gagal mengirim pesan.');
                        return data;
                    });
                }).then(function (data) {
                    if (requestId !== chatRequestId) return;
                    if (!chatList.querySelector('.konten-chat__item')) chatList.replaceChildren();
                    chatList.appendChild(chatMessage(data.chat));
                    chatList.scrollTop = chatList.scrollHeight;
                    chatInput.value = '';
                }).catch(function (error) {
                    if (requestId !== chatRequestId) return;
                    chatError.textContent = error.message;
                    chatError.hidden = false;
                }).finally(function () {
                    if (requestId === chatRequestId) chatButton.disabled = false;
                });
            });

            if (gagalEdit && @json($errors->any())) {
                const tombol = Array.from(document.querySelectorAll('[data-bs-target="#editKontenModal"]'))
                    .find(function (item) { return item.dataset.kontenId === String(gagalEdit.kontenId); });
                if (tombol) bootstrap.Modal.getOrCreateInstance(modal).show(tombol);
            }
        });
    </script>
@endpush

@push('after-styles')
    <style>
        .konten-chat__list { display: flex; flex-direction: column; gap: .65rem; max-height: 220px; overflow-y: auto; }
        .konten-chat__meta { display: flex; justify-content: space-between; gap: .75rem; margin-bottom: .2rem; font-size: .8rem; }
        .konten-chat__bubble { padding: .55rem .75rem; border-radius: 10px; background: rgba(127, 127, 127, .12); white-space: pre-wrap; overflow-wrap: anywhere; }
    </style>
@endpush
