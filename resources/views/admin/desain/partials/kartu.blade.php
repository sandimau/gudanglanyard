@php
    $produkLabel = $desain->produk ? $desain->produk_label : '';
    $tipeLabel = $desain->tipe_label;
    $teksPencarian = mb_strtolower($desain->nama_konsumen . ' ' . $produkLabel . ' ' . $desain->tema . ' ' . $tipeLabel . ' ' . $desain->keterangan);
@endphp
<tr class="desain-row" data-tipe="{{ $desain->tipe }}" data-search="{{ $teksPencarian }}">
    <td>
        <a class="desain-row__nama" href="{{ route('desain.show', $desain) }}"
            data-bs-toggle="modal" data-bs-target="#detailDesainModal" data-detail-url="{{ route('desain.show', $desain) }}">{{ $desain->nama_konsumen }}</a>
    </td>
    <td>{{ $produkLabel ?: '-' }}</td>
    <td>{{ $desain->tema ?: '-' }}</td>
    <td><span class="badge {{ $desain->tipe === 'online' ? 'bg-success' : 'bg-secondary' }}">{{ $tipeLabel }}</span></td>
    @if ($allTabs->isNotEmpty())
        <td>
            <form method="POST" action="{{ route('desain.updateTab', $desain) }}">
                @csrf @method('PATCH')
                <label class="visually-hidden" for="desain-tab-{{ $desain->id }}">Pilih tab proses</label>
                <select id="desain-tab-{{ $desain->id }}" name="desain_tab_id"
                    class="form-select form-select-sm desain-row__status" data-desain-status-select required>
                    <option value="" disabled {{ $desain->desain_tab_id ? '' : 'selected' }}>Pilih tab proses</option>
                    @foreach ($allTabs as $pilihan)
                        <option value="{{ $pilihan->id }}" {{ $desain->desain_tab_id == $pilihan->id ? 'selected' : '' }}>
                            {{ $pilihan->nama }}
                        </option>
                    @endforeach
                </select>
            </form>
        </td>
    @endif
    <td style="min-width: 8rem;">
        <form method="POST" action="{{ route('desain.updatePemproses', $desain) }}">
            @csrf @method('PATCH')
            <label class="visually-hidden" for="desain-pemproses-{{ $desain->id }}">Pilih pemproses</label>
            <select id="desain-pemproses-{{ $desain->id }}" name="pemproses_id"
                class="form-select form-select-sm" onchange="this.form.requestSubmit()">
                <option value="">- pilih -</option>
                @foreach ($pemprosesSetting as $entry)
                    <option value="{{ $entry->id }}" {{ $desain->pemproses_id == $entry->id ? 'selected' : '' }}>{{ $entry->nama }}</option>
                @endforeach
            </select>
        </form>
    </td>
</tr>
