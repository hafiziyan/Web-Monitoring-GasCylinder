@props([
    'selected' => null,
    'allowedStatuses' => [],
    'searchUrl' => '',
    'searchAction' => '',
    'initialKeyword' => '',
    'inputName' => 'cylinder_id',
    'formId' => 'transaction-form',
    'showLastDestination' => false,
])

@php
    $uid = 'qr-' . uniqid();
    $qrIds = [
        'reader' => $uid . '-reader',
        'status' => $uid . '-status',
        'error' => $uid . '-error',
        'select' => $uid . '-camera',
        'manual' => $uid . '-manual',
        'file' => $uid . '-file',
    ];
    $qrConfig = [
        'initial' => $selected,
        'allowed' => array_values($allowedStatuses),
        'searchUrl' => $searchUrl,
        'ids' => $qrIds,
    ];
@endphp

@once
    @push('scripts')
        @vite(['resources/js/scan.js'])
    @endpush
@endonce

<script type="application/json" id="{{ $uid }}-config">@json($qrConfig)</script>
<div
    x-data="cylinderSelector('{{ $uid }}')"
    class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm"
>
    {{-- Header langkah --}}
    <div class="flex items-center gap-3 border-b border-slate-100 bg-gradient-to-r from-slate-900 to-slate-700 px-5 py-4">
        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-white/15 text-sm font-bold text-white ring-1 ring-white/30">1</span>
        <div class="min-w-0">
            <h3 class="text-sm font-semibold text-white">Pilih Tabung</h3>
            <p class="truncate text-xs text-slate-300">Scan QR, cari, atau pilih dari daftar — wajib sebelum mengisi transaksi.</p>
        </div>
        <span x-show="detail" x-cloak class="ml-auto inline-flex shrink-0 items-center gap-1.5 rounded-full bg-emerald-400/15 px-3 py-1 text-xs font-semibold text-emerald-300 ring-1 ring-emerald-300/30">
            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
            Terpilih
        </span>
    </div>

    <div class="p-5">
        {{-- Cara 1: Scan QR --}}
        <button type="button" @click="openScanner()"
            class="inline-flex w-full items-center justify-center gap-2.5 rounded-xl bg-slate-900 px-4 py-3.5 text-sm font-semibold text-white shadow-sm transition hover:bg-slate-700 focus:outline-none focus:ring-2 focus:ring-slate-500 focus:ring-offset-2 active:scale-[.99] sm:w-auto sm:px-8">
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M3.75 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5A1.125 1.125 0 0 1 3.75 9.375v-4.5Zm9.75 0c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5a1.125 1.125 0 0 1-1.125-1.125v-4.5ZM3.75 14.625c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5a1.125 1.125 0 0 1-1.125-1.125v-4.5Zm9.75 0c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5a1.125 1.125 0 0 1-1.125-1.125v-4.5Z" />
            </svg>
            Scan QR Code
            <span class="hidden rounded-md bg-white/15 px-2 py-0.5 text-[11px] font-medium text-slate-200 sm:inline">kamera / upload</span>
        </button>
        <p class="mt-2 text-xs text-slate-500">Gunakan kamera HP/laptop, atau upload foto QR jika kamera tidak tersedia.</p>

        {{-- Cara 2: Search --}}
        <div class="mt-5 flex items-center gap-3">
            <span class="h-px flex-1 bg-slate-200"></span>
            <span class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">atau cari tabung</span>
            <span class="h-px flex-1 bg-slate-200"></span>
        </div>

        <div class="relative mt-3" x-show="!detail">
            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5">
                <svg x-show="!loading" class="h-5 w-5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" /></svg>
                <svg x-show="loading" x-cloak class="h-5 w-5 animate-spin text-slate-500" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 0 1 8-8v4a4 4 0 0 0-4 4H4z"/></svg>
            </div>
            <input type="text" x-model="query" @input="onInput()" @focus="if (query.trim() !== '') open = true" @click.away="open = false"
                placeholder="Cari kode tabung, serial number, atau QR token..."
                class="w-full rounded-xl border-slate-300 py-3 pl-11 pr-4 shadow-sm focus:border-slate-500 focus:ring-slate-500" />
            <div x-show="open" x-cloak x-transition
                class="absolute z-20 mt-1.5 max-h-72 w-full overflow-y-auto rounded-xl border border-slate-200 bg-white shadow-xl">
                <template x-if="loading">
                    <p class="px-4 py-3 text-sm text-slate-500">Mencari...</p>
                </template>
                <template x-if="!loading && searched && results.length === 0">
                    <p class="px-4 py-3 text-sm text-slate-500">Tabung tidak ditemukan. Coba kata kunci lain atau scan QR.</p>
                </template>
                <template x-if="!loading && !searched">
                    <p class="px-4 py-3 text-sm text-slate-500">Masukkan kode tabung, serial number, atau QR token.</p>
                </template>
                <template x-for="item in results" :key="item.id">
                    <button type="button" @click="select(item)"
                        class="block w-full border-b border-slate-50 px-4 py-3 text-left transition last:border-0 hover:bg-slate-50">
                        <p class="flex items-center justify-between gap-2 text-sm font-semibold text-slate-900">
                            <span x-text="item.cylinder_code"></span>
                            <span class="rounded-full px-2 py-0.5 text-[11px] font-semibold" :class="statusClass(item.status)" x-text="item.status"></span>
                        </p>
                        <p class="mt-0.5 text-xs text-slate-600">
                            <span x-text="item.gas_type"></span> • <span x-text="item.serial_number ?? '-'"></span>
                        </p>
                        <p class="text-xs text-slate-400">
                            <span x-text="item.current_location ?? 'Di vendor luar'"></span>
                        </p>
                    </button>
                </template>
            </div>
        </div>

        {{-- Cara 3: Pilih dari daftar --}}
        <div class="mt-4 flex items-center gap-3" x-show="!detail">
            <span class="h-px flex-1 bg-slate-200"></span>
            <span class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">atau pilih dari daftar</span>
            <span class="h-px flex-1 bg-slate-200"></span>
        </div>
        <div class="mt-3" x-show="!detail">
            <button type="button" @click="toggleList()"
                class="inline-flex w-full items-center justify-center gap-2 rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm font-medium text-slate-700 shadow-sm transition hover:bg-slate-50 sm:w-auto">
                <svg class="h-5 w-5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M8.25 6.75h12M8.25 12h12m-12 5.25h12M3.75 6.75h.007v.008H3.75V6.75Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0ZM3.75 12h.007v.008H3.75V12Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm-.375 5.25h.007v.008H3.75v-.008Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                </svg>
                <span x-text="listOpen ? 'Sembunyikan Daftar' : 'Pilih Tabung dari Daftar'"></span>
                <svg class="h-4 w-4 text-slate-400 transition-transform" :class="listOpen && 'rotate-180'" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" /></svg>
            </button>
            <div x-show="listOpen" x-cloak x-transition class="mt-2 max-h-72 overflow-y-auto rounded-xl border border-slate-200">
                <template x-if="listLoading">
                    <p class="px-4 py-3 text-sm text-slate-500">Memuat daftar...</p>
                </template>
                <template x-if="!listLoading && listResults.length === 0">
                    <p class="px-4 py-3 text-sm text-slate-500">Tidak ada tabung yang sesuai filter status transaksi ini.</p>
                </template>
                <template x-for="item in listResults" :key="'list-' + item.id">
                    <button type="button" @click="select(item)"
                        class="block w-full border-b border-slate-100 px-4 py-3 text-left transition last:border-0 hover:bg-slate-50">
                        <p class="flex items-center justify-between gap-2 text-sm font-semibold text-slate-900">
                            <span x-text="item.cylinder_code"></span>
                            <span class="rounded-full px-2 py-0.5 text-[11px] font-semibold" :class="statusClass(item.status)" x-text="item.status"></span>
                        </p>
                        <p class="mt-0.5 text-xs text-slate-600">
                            <span x-text="item.gas_type"></span> • <span x-text="item.serial_number ?? '-'"></span>
                        </p>
                        <p class="text-xs text-slate-400">
                            <span x-text="item.current_location ?? 'Di vendor luar'"></span>
                        </p>
                    </button>
                </template>
            </div>
        </div>

        {{-- Kartu tabung terpilih --}}
        <div x-show="detail" x-cloak x-transition class="mt-4 overflow-hidden rounded-xl border border-emerald-200 bg-emerald-50/40">
            <div class="flex flex-wrap items-center justify-between gap-2 border-b border-emerald-100 bg-white/60 px-4 py-3">
                <div class="flex items-center gap-2.5">
                    <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-slate-900 text-white">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
                    </span>
                    <div>
                        <h4 class="text-sm font-bold text-slate-900" x-text="detail?.cylinder_code"></h4>
                        <p class="text-xs text-slate-500"><span x-text="detail?.gas_type"></span> • <span class="font-mono" x-text="detail?.qr_token"></span></p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <span class="rounded-full px-2.5 py-1 text-[11px] font-bold" :class="statusClass(detail?.status)" x-text="detail?.status"></span>
                    <button type="button" @click="clear()"
                        class="rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50">Ganti Tabung</button>
                </div>
            </div>
            <dl class="grid grid-cols-1 gap-x-6 px-4 py-2 text-sm sm:grid-cols-2">
                <div class="flex justify-between gap-4 border-b border-slate-100 py-2"><dt class="text-slate-500">Serial Number</dt><dd class="font-medium text-slate-900" x-text="detail?.serial_number ?? '-'"></dd></div>
                <div class="flex justify-between gap-4 border-b border-slate-100 py-2"><dt class="text-slate-500">Kapasitas</dt><dd class="font-medium text-slate-900" x-text="(detail?.capacity ?? '-') + ' ' + (detail?.unit ?? '')"></dd></div>
                <div class="flex justify-between gap-4 border-b border-slate-100 py-2"><dt class="text-slate-500">Supplier</dt><dd class="font-medium text-slate-900" x-text="detail?.supplier ?? '-'"></dd></div>
                <div class="flex justify-between gap-4 border-b border-slate-100 py-2"><dt class="text-slate-500">Lokasi</dt><dd class="font-medium text-slate-900" x-text="detail?.current_location ?? 'Di vendor luar (OUTSIDE)'"></dd></div>
                <div class="flex justify-between gap-4 border-b border-slate-100 py-2"><dt class="text-slate-500">Kondisi</dt><dd class="font-medium text-slate-900" x-text="detail?.condition"></dd></div>
                @if ($showLastDestination)
                    <div class="flex justify-between gap-4 border-b border-slate-100 py-2"><dt class="text-slate-500">Vendor Terakhir</dt><dd class="font-medium text-slate-900" x-text="detail?.last_destination ?? '-'"></dd></div>
                @endif
            </dl>
            <p x-show="detail && allowed.length > 0 && !allowed.includes(detail.status)" x-cloak
                class="mx-4 mb-3 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-800">
                Status tabung ini (<span class="font-semibold" x-text="detail?.status"></span>) tidak valid untuk transaksi ini. Server tetap akan menolak saat submit — silakan ganti tabung.
            </p>
        </div>
    </div>

    <input type="hidden" name="{{ $inputName }}" value="{{ old($inputName, $selected['id'] ?? '') }}" :value="detail?.id ?? ''" form="{{ $formId }}" />

    @error($inputName)
        <p class="px-5 pb-4 text-sm text-red-600">{{ $message }}</p>
    @enderror

    {{-- Modal scanner kamera --}}
    <div x-show="scannerOpen" x-cloak class="fixed inset-0 z-50 flex items-end justify-center bg-slate-950/70 p-0 backdrop-blur-sm sm:items-center sm:p-6"
        @click.self="closeScanner()" @keydown.escape.window="closeScanner()" role="dialog" aria-modal="true" aria-label="Scan QR Code"
        x-transition.opacity>
        <div class="max-h-[92vh] w-full max-w-md overflow-y-auto rounded-t-2xl bg-white p-5 shadow-2xl sm:rounded-2xl"
            x-transition.scale.90>
            <div class="flex items-center justify-between">
                <div>
                    <h4 class="text-base font-bold text-slate-900">Scan QR Code</h4>
                    <p class="text-xs text-slate-500">Kamera, upload foto, atau ketik manual</p>
                </div>
                <button type="button" @click="closeScanner()" aria-label="Tutup"
                    class="rounded-lg p-2 text-slate-400 transition hover:bg-slate-100 hover:text-slate-600">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
                </button>
            </div>

            {{-- Viewfinder --}}
            <div class="relative mt-4">
                <div id="{{ $uid }}-reader" class="qr-viewfinder min-h-[240px] overflow-hidden rounded-xl bg-slate-950"></div>
                <div class="pointer-events-none absolute inset-0 flex items-center justify-center">
                    <div class="h-44 w-44 rounded-xl border-2 border-white/70 shadow-[0_0_0_9999px_rgba(2,6,23,.45)]"></div>
                </div>
            </div>
            <div class="mt-2 flex items-center gap-2 text-sm">
                <span class="relative flex h-2.5 w-2.5">
                    <span x-show="!scanFailed && !uploading" class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-75"></span>
                    <span x-show="!scanFailed && !uploading" class="relative inline-flex h-2.5 w-2.5 rounded-full bg-emerald-500"></span>
                    <span x-show="scanFailed" x-cloak class="relative inline-flex h-2.5 w-2.5 rounded-full bg-red-500"></span>
                    <span x-show="uploading" x-cloak class="relative inline-flex h-2.5 w-2.5 animate-pulse rounded-full bg-amber-500"></span>
                </span>
                <p id="{{ $uid }}-status" class="text-slate-600">Menyiapkan kamera...</p>
            </div>
            <p id="{{ $uid }}-error" class="mt-2 hidden rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-800"></p>

            <div class="mt-3" data-camera-wrap>
                <label for="{{ $uid }}-camera" class="text-sm font-medium text-slate-700">Pilih Kamera</label>
                <select id="{{ $uid }}-camera" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-slate-500 focus:ring-slate-500"></select>
            </div>

            {{-- Fallback: upload --}}
            <div class="mt-4 rounded-xl border border-dashed border-slate-300 bg-slate-50 p-4">
                <label class="text-sm font-semibold text-slate-700">Kamera bermasalah? Upload foto QR</label>
                <div class="mt-2 flex flex-col gap-2 sm:flex-row">
                    <label class="inline-flex flex-1 cursor-pointer items-center justify-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 shadow-sm transition hover:bg-slate-50">
                        <svg class="h-5 w-5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5m-13.5-9L12 3m0 0 4.5 4.5M12 3v13.5" /></svg>
                        <span x-text="uploading ? 'Memproses...' : 'Pilih Foto QR'"></span>
                        <input id="{{ $uid }}-file" data-qr-file type="file" accept="image/*" class="sr-only" @change="handleFile($event)" :disabled="uploading" />
                    </label>
                    <button type="button" @click="rescan()" x-show="scanFailed || uploading === false" class="rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 shadow-sm transition hover:bg-slate-50">Scan Lagi</button>
                </div>
                <p class="mt-1.5 text-xs text-slate-500">Tips: foto harus jelas, QR tidak blur / terpotong.</p>
            </div>

            {{-- Fallback: manual --}}
            <div class="mt-3 border-t border-slate-100 pt-3">
                <label for="{{ $uid }}-manual" class="text-sm font-semibold text-slate-700">Atau masukkan QR Token manual</label>
                <div class="mt-1.5 flex flex-col gap-2 sm:flex-row">
                    <input id="{{ $uid }}-manual" type="text" x-model="manualToken" @keydown.enter.prevent="useManualToken()"
                        placeholder="cth: CYL-8F42A91D" autocapitalize="characters" spellcheck="false"
                        class="flex-1 rounded-lg border-slate-300 py-2.5 font-mono uppercase shadow-sm focus:border-slate-500 focus:ring-slate-500" />
                    <button type="button" @click="useManualToken()" :disabled="lookingUp"
                        class="rounded-lg bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-700 disabled:opacity-50">
                        <span x-show="!lookingUp">Gunakan</span>
                        <span x-show="lookingUp" x-cloak>Mencari...</span>
                    </button>
                </div>
            </div>
            <p class="mt-3 text-center text-xs text-slate-400">Arahkan kamera ke QR Code tabung. Hasil terbaca otomatis.</p>
        </div>
    </div>

    <noscript>
        <form method="GET" action="{{ $searchAction }}" class="p-5 pt-0 flex flex-col gap-3 sm:flex-row">
            <input type="text" name="cylinder" value="{{ $initialKeyword }}" placeholder="QR Token / Kode Tabung..."
                class="flex-1 rounded-lg border-slate-300 shadow-sm" />
            <button type="submit" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700">Cari Tabung</button>
        </form>
    </noscript>

    <script>
        window.cylinderSelector = window.cylinderSelector || function (uid) {
            // Config dibaca dari <script type="application/json"> agar tanda kutip
            // pada JSON tidak merusak atribut x-data (sebelumnya tombol Scan mati).
            let config = { initial: null, allowed: [], searchUrl: '', ids: {} };
            try {
                const cfgEl = document.getElementById(uid + '-config');
                if (cfgEl && cfgEl.textContent.trim() !== '') {
                    const parsed = JSON.parse(cfgEl.textContent);
                    config = Object.assign(config, parsed || {});
                }
            } catch (e) {
                // Fallback ke defaults; pencarian manual tetap bisa dipakai.
            }
            config.allowed = Array.isArray(config.allowed) ? config.allowed : [];
            const ids = config.ids || {};
            const el = (key) => document.getElementById(ids[key] || key);
            const normalize = (raw) => {
                if (window.QrScanner && window.QrScanner.normalizeToken) {
                    return window.QrScanner.normalizeToken(raw);
                }
                const m = String(raw || '').trim().match(/CYL-[A-Z0-9]{4,}/i);
                if (m) return m[0].toUpperCase();
                return String(raw || '').trim().toUpperCase();
            };
            return {
                query: '',
                results: [],
                open: false,
                loading: false,
                searched: false,
                detail: config.initial || null,
                timer: null,
                listOpen: false,
                listResults: [],
                listLoading: false,
                scannerOpen: false,
                scanFailed: false,
                uploading: false,
                lookingUp: false,
                manualToken: '',
                statusClass(status) {
                    switch (status) {
                        case 'AVAILABLE': return 'bg-emerald-100 text-emerald-800';
                        case 'IN_USE': return 'bg-blue-100 text-blue-800';
                        case 'EMPTY': return 'bg-slate-200 text-slate-700';
                        case 'MAINTENANCE': return 'bg-amber-100 text-amber-800';
                        case 'OUTSIDE': return 'bg-purple-100 text-purple-800';
                        case 'INACTIVE': return 'bg-red-100 text-red-700';
                        default: return 'bg-slate-100 text-slate-600';
                    }
                },
                fetchResults(q) {
                    const params = new URLSearchParams();
                    if (q) params.append('q', q);
                    if (config.allowed.length > 0) params.append('status', config.allowed.join(','));
                    return fetch(`${config.searchUrl}?${params.toString()}`, { headers: { 'Accept': 'application/json' } })
                        .then((r) => {
                            if (!r.ok) throw new Error('search-failed');
                            return r.json();
                        });
                },
                onInput() {
                    clearTimeout(this.timer);
                    const q = this.query.trim();
                    if (q === '') {
                        this.results = [];
                        this.searched = false;
                        this.open = false;
                        return;
                    }
                    this.loading = true;
                    this.open = true;
                    this.timer = setTimeout(() => {
                        this.fetchResults(q)
                            .then((data) => {
                                this.results = data;
                                this.searched = true;
                            })
                            .catch(() => {
                                this.results = [];
                                this.searched = true;
                            })
                            .finally(() => { this.loading = false; });
                    }, 300);
                },
                toggleList() {
                    this.listOpen = !this.listOpen;
                    if (this.listOpen && this.listResults.length === 0) {
                        this.listLoading = true;
                        this.fetchResults('')
                            .then((data) => { this.listResults = data; })
                            .catch(() => { this.listResults = []; })
                            .finally(() => { this.listLoading = false; });
                    }
                },
                select(item) {
                    this.detail = item;
                    this.open = false;
                    this.query = '';
                    this.results = [];
                    this.listOpen = false;
                    window.dispatchEvent(new CustomEvent('cylinder-selected', { detail: item }));
                },
                clear() {
                    this.detail = null;
                    this.query = '';
                    this.results = [];
                    this.searched = false;
                    this.open = false;
                    this.listOpen = false;
                    this.listResults = [];
                    window.dispatchEvent(new CustomEvent('cylinder-cleared'));
                },
                showScannerError(message) {
                    const errorEl = el('error');
                    if (errorEl) {
                        errorEl.textContent = message;
                        errorEl.classList.remove('hidden');
                    }
                    const statusEl = el('status');
                    if (statusEl) statusEl.textContent = '';
                },
                clearScannerError() {
                    const errorEl = el('error');
                    if (errorEl) {
                        errorEl.textContent = '';
                        errorEl.classList.add('hidden');
                    }
                },
                openScanner() {
                    this.scannerOpen = true;
                    this.scanFailed = false;
                    this.uploading = false;
                    this.manualToken = '';
                    this.clearScannerError();
                    const statusEl = el('status');
                    if (statusEl) statusEl.textContent = 'Menyiapkan kamera...';
                    // Reset reader agar Html5Qrcode bisa mount ulang (bug utama: sisa DOM scan sebelumnya).
                    const readerEl = el('reader');
                    if (readerEl) readerEl.innerHTML = '';
                    if (!window.QrScanner) {
                        this.$nextTick(() => {
                            this.showScannerError('Modul pemindai gagal dimuat (scan.js belum ter-build). Jalankan "npm run dev/build" atau masukkan QR Token secara manual / upload foto.');
                            const st = el('status');
                            if (st) st.textContent = 'Kamera tidak tersedia — gunakan input manual di bawah.';
                        });
                        return;
                    }
                    this.$nextTick(() => {
                        window.QrScanner.open({
                            readerId: ids.reader,
                            selectId: ids.select,
                            statusId: ids.status,
                            errorId: ids.error,
                            onDecode: (text) => this.onScanned(text),
                        });
                    });
                },
                closeScanner() {
                    const done = () => { this.scannerOpen = false; this.uploading = false; };
                    if (window.QrScanner) {
                        const p = window.QrScanner.close();
                        if (p && typeof p.finally === 'function') {
                            p.finally(done);
                        } else {
                            done();
                        }
                    } else {
                        done();
                    }
                },
                rescan() {
                    this.scanFailed = false;
                    this.clearScannerError();
                    // Tutup dulu stream lama sebelum start ulang agar tidak "camera in use".
                    const restart = () => this.openScanner();
                    if (window.QrScanner) {
                        const p = window.QrScanner.close();
                        if (p && typeof p.then === 'function') {
                            p.then(restart).catch(restart);
                            return;
                        }
                    }
                    restart();
                },
                onScanned(raw) {
                    const code = normalize(raw);
                    if (!code) return;
                    // Hentikan kamera dulu agar tidak double-decode, lalu lookup.
                    const proceed = () => this.lookupToken(code);
                    if (window.QrScanner) {
                        const p = window.QrScanner.close();
                        if (p && typeof p.finally === 'function') {
                            p.finally(proceed);
                            return;
                        }
                    }
                    proceed();
                },
                async handleFile(event) {
                    const file = event.target.files && event.target.files[0];
                    if (!file) return;
                    const code0 = normalize(file.name.replace(/\.[^.]+$/, ''));
                    this.clearScannerError();
                    if (!window.QrScanner) {
                        this.showScannerError('Modul pemindai belum dimuat. Ketik token manual.');
                        return;
                    }
                    this.uploading = true;
                    const statusEl = el('status');
                    if (statusEl) statusEl.textContent = 'Membaca gambar QR...';
                    try {
                        const raw = await window.QrScanner.scanFile(file, ids.reader);
                        const code = normalize(raw);
                        this.uploading = false;
                        if (code) {
                            this.lookupToken(code, { keepOpen: true });
                        } else {
                            this.scanFailed = true;
                            this.showScannerError('QR tidak terbaca dari gambar. Coba foto yang lebih jelas atau ketik manual.');
                        }
                    } catch (e) {
                        this.uploading = false;
                        this.scanFailed = true;
                        // Jika nama file mengandung token, tawarkan sebagai fallback.
                        if (/^CYL-[A-Z0-9]{4,}$/i.test(code0)) {
                            this.manualToken = code0;
                        }
                        this.showScannerError('QR tidak terbaca dari gambar tersebut. Pastikan gambar memuat satu QR yang jelas, atau ketik token manual.');
                    } finally {
                        // Reset input agar file yang sama bisa dipilih ulang.
                        try { event.target.value = ''; } catch (e) {}
                        // Kamera tetap jalan di belakang; kembalikan status jika masih terbuka.
                        if (this.scannerOpen && !this.detail) {
                            const st = el('status');
                            if (st && !this.scanFailed) st.textContent = 'Arahkan kamera ke QR Code tabung.';
                        }
                    }
                },
                useManualToken() {
                    const code = normalize(this.manualToken);
                    if (!code) {
                        this.showScannerError('Masukkan QR Token terlebih dahulu. Contoh: CYL-8F42A91D.');
                        return;
                    }
                    this.lookupToken(code, { keepOpen: true });
                },
                lookupToken(code, opts = {}) {
                    this.lookingUp = true;
                    this.clearScannerError();
                    const params = new URLSearchParams({ q: code });
                    if (config.allowed.length > 0) params.append('status', config.allowed.join(','));
                    fetch(`${config.searchUrl}?${params.toString()}`, { headers: { 'Accept': 'application/json' } })
                        .then((r) => {
                            if (!r.ok) throw new Error('lookup-failed');
                            return r.json();
                        })
                        .then((data) => {
                            const list = data || [];
                            const upper = String(code).toUpperCase();
                            const found = list.find((item) => String(item.qr_token || '').toUpperCase() === upper)
                                || list.find((item) => String(item.cylinder_code || '').toUpperCase() === upper)
                                || list.find((item) => String(item.serial_number || '').toUpperCase() === upper)
                                || list[0];
                            // Jika tidak ketemu dalam filter status, cek tanpa filter untuk pesan yang akurat.
                            if (!found && config.allowed.length > 0) {
                                return fetch(`${config.searchUrl}?${new URLSearchParams({ q: code }).toString()}`, { headers: { 'Accept': 'application/json' } })
                                    .then((r) => (r.ok ? r.json() : []))
                                    .then((all) => {
                                        const any = (all || []).find((item) => String(item.qr_token || '').toUpperCase() === upper)
                                            || (all || []).find((item) => String(item.cylinder_code || '').toUpperCase() === upper);
                                        if (any) {
                                            this.scanFailed = true;
                                            this.showScannerError(`Tabung ${any.cylinder_code} ditemukan tetapi statusnya "${any.status}" — tidak valid untuk transaksi ini.`);
                                        } else {
                                            this.scanFailed = true;
                                            this.showScannerError(`QR "${code}" tidak terdaftar. Periksa cetakan QR atau ketik manual dengan teliti.`);
                                        }
                                    });
                            }
                            if (found) {
                                this.scanFailed = false;
                                this.lookingUp = false;
                                this.closeScanner();
                                // Kecilkan jeda agar modal sempat menutup stream sebelum select.
                                setTimeout(() => this.select(found), 50);
                                return null;
                            } else {
                                this.scanFailed = true;
                                this.showScannerError(`QR "${code}" tidak terdaftar. Periksa cetakan QR atau ketik manual dengan teliti.`);
                            }
                        })
                        .catch(() => {
                            this.scanFailed = true;
                            this.showScannerError('Gagal mencari tabung. Periksa koneksi lalu tekan "Scan Lagi".');
                        })
                        .finally(() => { this.lookingUp = false; });
                },
            };
        };
    </script>
    <style>
        .qr-viewfinder video {
            width: 100% !important;
            height: 100% !important;
            min-height: 240px;
            object-fit: cover;
            border-radius: 0.75rem;
        }
        .qr-viewfinder canvas {
            display: none;
        }
    </style>
</div>
