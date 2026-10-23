<tr class="konten-row" data-search="{{ mb_strtolower($konten->topik . ' ' . $konten->platform . ' ' . $konten->tujuan) }}">
    <td>
        <button type="button" class="konten-row__topik" data-bs-toggle="modal"
            data-bs-target="#editKontenModal" data-konten-id="{{ $konten->id }}"
            data-action="{{ route('konten.update', $konten) }}"
            data-delete-action="{{ route('konten.destroy', $konten) }}"
            data-chat-url="{{ route('konten.chat.index', $konten) }}"
            data-chat-store-url="{{ route('konten.chat.store', $konten) }}"
            data-tanggal="{{ $konten->tanggal->toDateString() }}"
            data-topik="{{ $konten->topik }}" data-platform="{{ $konten->platform }}"
            data-waktu-posting="{{ substr($konten->waktu_posting, 0, 5) }}"
            data-tujuan="{{ $konten->tujuan }}"
            title="Lihat dan edit {{ $konten->topik }}">{{ $konten->topik }}</button>
    </td>
    <td class="text-nowrap">{{ substr($konten->waktu_posting, 0, 5) }}</td>
    <td>{{ $konten->platform }}</td>
    <td class="konten-row__tujuan">{{ $konten->tujuan }}</td>
    @if ($allTabs->isNotEmpty())
        @php
            $indexTab = $allTabs->search(fn ($tab) => $tab->id === $konten->konten_tab_id);
            $tabBerikutnya = $indexTab === false ? $allTabs->first() : $allTabs->get($indexTab + 1);
        @endphp
        <td>
            <div class="konten-row__actions">
                <form method="POST" action="{{ route('konten.updateTab', $konten) }}">
                    @csrf @method('PATCH')
                    <label class="visually-hidden" for="konten-tab-{{ $konten->id }}">Pilih tab proses</label>
                    <select id="konten-tab-{{ $konten->id }}" name="konten_tab_id"
                        class="form-select form-select-sm konten-row__status" data-konten-status-select required>
                        <option value="" disabled {{ $konten->konten_tab_id ? '' : 'selected' }}>Pilih tab proses</option>
                        @foreach ($allTabs as $pilihan)
                            <option value="{{ $pilihan->id }}" {{ $konten->konten_tab_id == $pilihan->id ? 'selected' : '' }}>
                                {{ $pilihan->nama }}
                            </option>
                        @endforeach
                    </select>
                </form>
                @if ($tabBerikutnya)
                    <form method="POST" action="{{ route('konten.updateTab', $konten) }}" data-konten-next-form>
                        @csrf @method('PATCH')
                        <input type="hidden" name="konten_tab_id" value="{{ $tabBerikutnya->id }}">
                        <button type="submit" class="btn btn-primary btn-sm konten-row__next"
                            aria-label="Lanjut ke {{ $tabBerikutnya->nama }}" title="Lanjut ke {{ $tabBerikutnya->nama }}">
                            <span class="konten-row__next-label">Lanjut ke {{ $tabBerikutnya->nama }}</span>
                            <i class="bx bx-right-arrow-alt" aria-hidden="true"></i>
                        </button>
                    </form>
                @endif
            </div>
        </td>
    @endif
</tr>
