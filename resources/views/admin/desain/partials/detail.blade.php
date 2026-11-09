<div class="d-flex justify-content-between align-items-center gap-2 mb-3">
    <h5 class="mb-0">{{ $desain->nama_konsumen }}</h5>
    <button type="button" class="btn btn-primary btn-sm" data-desain-edit
        data-bs-toggle="modal" data-bs-target="#editDesainModal"
        data-desain-id="{{ $desain->id }}" data-action="{{ route('desain.update', $desain) }}"
        data-delete-action="{{ route('desain.destroy', $desain) }}"
        data-nama-konsumen="{{ $desain->nama_konsumen }}"
        data-produk-id="{{ $desain->produk_id }}"
        data-produk-label="{{ $desain->produk ? $desain->produk_label : '' }}"
        data-tema="{{ $desain->tema }}" data-tipe="{{ $desain->tipe }}"
        data-keterangan="{{ $desain->keterangan }}">Edit</button>
</div>
<div class="row g-3">
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header"><h6 class="mb-0">Informasi Desain</h6></div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-4">Konsumen</dt><dd class="col-sm-8">{{ $desain->nama_konsumen }}</dd>
                    <dt class="col-sm-4">Produk</dt><dd class="col-sm-8">{{ $desain->produk ? $desain->produk_label : '-' }}</dd>
                    <dt class="col-sm-4">Tema</dt><dd class="col-sm-8">{{ $desain->tema ?: '-' }}</dd>
                    <dt class="col-sm-4">Tipe</dt><dd class="col-sm-8">{{ $desain->tipe_label }}</dd>
                    <dt class="col-sm-4">Proses</dt><dd class="col-sm-8">{{ $desain->tab ? $desain->tab->grup.' / '.$desain->tab->nama : 'Belum Ada Sub Kategori' }}</dd>
                    <dt class="col-sm-4">Keterangan</dt><dd class="col-sm-8" style="white-space: pre-wrap; overflow-wrap: anywhere">{{ $desain->keterangan ?: '-' }}</dd>
                </dl>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header"><h6 class="mb-0"><i class="bx bx-message-dots me-1 text-primary"></i> Notes</h6></div>
            <div class="card-body">
                <form method="POST" action="{{ route('desain.chat.store', $desain) }}" data-desain-chat-form>
                    @csrf
                    <div class="input-group mb-3">
                        <input type="text" name="isi" required maxlength="5000" value="{{ old('isi') }}"
                            class="form-control" placeholder="Tulis catatan..." aria-label="Tulis catatan">
                        <button class="btn btn-primary" type="submit" title="Kirim"><i class="bx bx-send"></i></button>
                    </div>
                    <p class="text-danger small" data-desain-chat-error hidden></p>
                    @error('isi') <div class="text-danger small mb-3">{{ $message }}</div> @enderror
                </form>
                <ul class="list-unstyled mb-0 desain-chat-list" style="max-height: 420px; overflow-y: auto">
                    @forelse ($desain->chats as $chat)
                        @php
                            preg_match_all('~https?://(?:drive|docs)\.google\.com/\S+|[A-Za-z]:\\\\[^\r\n]*?(?=\s+(?:https?://(?:drive|docs)\.google\.com/|[A-Za-z]:\\\\)|\r?$|$)~imu', $chat->isi ?? '', $links);
                            $driveLinks = collect($links[0])->map(fn ($url) => rtrim($url, " \t.,;!?)]}>"))->unique();
                        @endphp
                        <li class="mb-3">
                            <div class="d-flex justify-content-between gap-2 small mb-1">
                                <strong class="text-primary">{{ $chat->author_name ?: 'Anonim' }}</strong>
                                <span class="text-muted">{{ $chat->created_at?->format('d/m/Y H:i') }}</span>
                            </div>
                            <div class="bg-light rounded p-2" style="white-space: pre-wrap; overflow-wrap: anywhere">{{ $chat->isi }}</div>
                            @foreach ($driveLinks as $driveLink)
                                @php $isLocalPath = preg_match('~^[A-Za-z]:\\\\~', $driveLink); @endphp
                                <button type="button" class="btn btn-sm btn-outline-secondary mt-1 me-1" data-desain-copy-link="{{ $driveLink }}"
                                    title="Salin {{ $isLocalPath ? 'path Google Drive' : 'link Google Drive' }}"
                                    aria-label="Salin {{ $isLocalPath ? 'path' : 'link' }} Google Drive {{ $loop->iteration }}">
                                    <i class="bx bx-copy" aria-hidden="true"></i> Salin {{ $isLocalPath ? 'path' : 'link' }}{{ $driveLinks->count() > 1 ? ' '.$loop->iteration : '' }}
                                </button>
                            @endforeach
                        </li>
                    @empty
                        <li class="text-muted small py-2">Belum ada catatan</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>
</div>
