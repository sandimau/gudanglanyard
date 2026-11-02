@php
    $containerId = $containerId ?? 'desain-produk-autocomplete';
    $inputId = $inputId ?? 'desain-produk-input';
    $hiddenId = $hiddenId ?? 'desain-produk-id';
    $formId = $formId ?? null;
    $valueId = $valueId ?? '';
    $valueLabel = $valueLabel ?? '';
    $invalid = $invalid ?? false;
@endphp

<div id="{{ $containerId }}" class="autocomplete desain-produk" data-desain-produk>
    <input type="text" id="{{ $inputId }}"
        class="autocomplete-input desain-produk__input {{ $invalid ? 'is-invalid' : '' }}"
        placeholder="Cari produk" aria-label="Cari produk" autocomplete="off" value="{{ $valueLabel }}"
        @if ($formId) form="{{ $formId }}" @endif>
    <span id="{{ $inputId }}-clear" class="desain-produk__clear"
        style="display: {{ $valueId ? 'block' : 'none' }}">
        <button type="button" class="btnClose btn-warning" aria-label="Hapus produk">
            <i class="bx bx-x-circle" aria-hidden="true"></i>
        </button>
    </span>
    <ul class="autocomplete-result-list"></ul>
    <input type="hidden" id="{{ $hiddenId }}" name="produk_id" value="{{ $valueId }}"
        @if ($formId) form="{{ $formId }}" @endif>
</div>
