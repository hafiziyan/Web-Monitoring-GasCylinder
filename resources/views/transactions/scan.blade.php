<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center gap-3">
            <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-slate-900 text-white shadow-sm">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M3.75 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5A1.125 1.125 0 0 1 3.75 9.375v-4.5Zm9.75 0c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5a1.125 1.125 0 0 1-1.125-1.125v-4.5ZM3.75 14.625c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5a1.125 1.125 0 0 1-1.125-1.125v-4.5Zm9.75 0c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5a1.125 1.125 0 0 1-1.125-1.125v-4.5Z" />
                </svg>
            </span>
            <div class="leading-tight">
                <h2 class="text-xl font-bold text-slate-900">Scan QR Code</h2>
                <p class="mt-0.5 text-sm text-slate-500">Identifikasi tabung secara cepat via kamera, foto, atau token manual.</p>
            </div>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="w-full space-y-5 px-4 sm:px-6 lg:px-8"
            x-data="qrScanPage()"
            @keydown.escape.window="closeScanner()">
            {{-- Kartu utama scan --}}
            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="bg-gradient-to-r from-slate-900 to-slate-700 px-5 py-5 text-center">
                    <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-white/10 ring-1 ring-white/25">
                        <svg class="h-7 w-7 text-white" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M3.75 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5A1.125 1.125 0 0 1 3.75 9.375v-4.5Zm9.75 0c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5a1.125 1.125 0 0 1-1.125-1.125v-4.5ZM3.75 14.625c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5a1.125 1.125 0 0 1-1.125-1.125v-4.5Zm9.75 0c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5a1.125 1.125 0 0 1-1.125-1.125v-4.5Z" />
                        </svg>
                    </div>
                    <p class="mt-3 text-sm text-slate-200">Arahkan kamera ke QR Code tabung untuk identifikasi cepat.</p>
                    <button id="qr-scan-start" type="button" data-result-base-url="{{ url('/transactions/scan') }}"
                        @click="openScanner()"
                        class="mt-4 inline-flex w-full items-center justify-center gap-2 rounded-xl bg-white px-4 py-3 text-sm font-bold text-slate-900 shadow-sm transition hover:bg-slate-100 active:scale-[.99] sm:w-auto sm:px-8">
                        Mulai Scan QR
                    </button>
                    <p x-show="moduleMissing" x-cloak class="mx-auto mt-2 max-w-sm rounded-lg bg-red-500/15 px-3 py-2 text-xs text-red-200">Modul kamera gagal dimuat. Gunakan upload foto / input manual di bawah.</p>
                </div>
                <div class="grid grid-cols-2 divide-x divide-slate-100 text-center text-xs text-slate-500">
                    <div class="px-3 py-3"><span class="font-semibold text-slate-700">1. Kamera</span><br />langsung dari HP / laptop</div>
                    <div class="px-3 py-3"><span class="font-semibold text-slate-700">2. Upload / Manual</span><br />jika kamera bermasalah</div>
                </div>
            </div>

            <div class="grid grid-cols-1 items-start gap-5 lg:grid-cols-2">
            {{-- Upload foto --}}
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <h3 class="text-sm font-bold text-slate-800">Upload Foto QR</h3>
                <p class="mt-0.5 text-xs text-slate-500">Alternatif saat kamera tidak tersedia (HTTPS / izin ditolak).</p>
                <label class="mt-3 flex cursor-pointer flex-col items-center justify-center gap-2 rounded-xl border border-dashed border-slate-300 bg-slate-50 px-4 py-6 text-center transition hover:bg-slate-100">
                    <svg class="h-8 w-8 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Zm10.5-11.25h.008v.008h-.008V8.25Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" /></svg>
                    <span class="text-sm font-semibold text-slate-700" x-text="uploading ? 'Memproses gambar...' : 'Klik untuk pilih foto QR'"></span>
                    <span class="text-xs text-slate-400">PNG / JPG, QR terlihat jelas</span>
                    <input type="file" accept="image/*" class="sr-only" @change="handleFile($event)" :disabled="uploading" />
                </label>
                <p x-show="fileError" x-cloak x-text="fileError" class="mt-2 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700"></p>
            </div>

            {{-- Manual --}}
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <h3 class="text-sm font-bold text-slate-800">Input Manual QR Token</h3>
                <form id="qr-manual-form" method="GET" action="{{ route('transactions.scan') }}" class="mt-3 flex flex-col gap-2 sm:flex-row" @submit="normaliseManual($event)">
                    <input id="qr-manual-token" name="token" type="text" placeholder="cth: CYL-8F42A91D" autocapitalize="characters" spellcheck="false"
                        class="flex-1 rounded-xl border-slate-300 py-3 font-mono uppercase shadow-sm focus:border-slate-500 focus:ring-slate-500" />
                    <button type="submit"
                        class="rounded-xl bg-slate-900 px-5 py-3 text-sm font-semibold text-white transition hover:bg-slate-700">Cari Tabung</button>
                </form>
            </div>
            </div>

            {{-- Modal scanner --}}
            <div x-show="scannerOpen" x-cloak class="fixed inset-0 z-50 flex items-end justify-center bg-slate-950/70 p-0 backdrop-blur-sm sm:items-center sm:p-6"
                @click.self="closeScanner()" @keydown.escape.window="closeScanner()"
                role="dialog" aria-modal="true" aria-label="Scan QR Code" x-transition.opacity>
                <div class="max-h-[92vh] w-full max-w-md overflow-y-auto rounded-t-2xl bg-white p-5 shadow-2xl sm:rounded-2xl" x-transition.scale.90>
                    <div class="flex items-center justify-between">
                        <div>
                            <h4 class="text-base font-bold text-slate-900">Scan QR Code</h4>
                            <p class="text-xs text-slate-500">Arahkan ke QR tabung</p>
                        </div>
                        <button type="button" @click="closeScanner()" aria-label="Tutup"
                            class="rounded-lg p-2 text-slate-400 transition hover:bg-slate-100 hover:text-slate-600">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
                        </button>
                    </div>
                    <div class="relative mt-4">
                        <div id="qr-reader" class="qr-viewfinder min-h-[240px] overflow-hidden rounded-xl bg-slate-950"></div>
                        <div class="pointer-events-none absolute inset-0 flex items-center justify-center">
                            <div class="h-44 w-44 rounded-xl border-2 border-white/70 shadow-[0_0_0_9999px_rgba(2,6,23,.45)]"></div>
                        </div>
                    </div>
                    <p id="qr-camera-status" class="mt-2 text-sm text-slate-600">Menyiapkan kamera...</p>
                    <p id="qr-scan-error" class="mt-2 hidden rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-800"></p>
                    <div class="mt-3" data-camera-wrap>
                        <label for="qr-camera-select" class="text-sm font-medium text-slate-700">Pilih Kamera</label>
                        <select id="qr-camera-select" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-slate-500 focus:ring-slate-500"></select>
                    </div>
                    <button type="button" @click="openScanner()" class="mt-3 w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">Scan Lagi</button>
                    <p class="mt-3 text-center text-xs text-slate-400">Hasil terbaca otomatis dan membuka detail tabung.</p>
                </div>
            </div>
        </div>
    </div>

    <script>
        function qrScanPage() {
            return {
                scannerOpen: false,
                uploading: false,
                fileError: '',
                moduleMissing: false,
                resultBaseUrl() {
                    return document.getElementById('qr-scan-start')?.dataset.resultBaseUrl || (window.location.origin + '/transactions/scan');
                },
                normalise(raw) {
                    if (window.QrScanner && window.QrScanner.normalizeToken) return window.QrScanner.normalizeToken(raw);
                    const m = String(raw || '').match(/CYL-[A-Z0-9]{4,}/i);
                    return (m ? m[0] : String(raw || '').trim()).toUpperCase();
                },
                goTo(token) {
                    const code = this.normalise(token);
                    if (!code) return;
                    window.location.href = `${this.resultBaseUrl()}/${encodeURIComponent(code)}`;
                },
                openScanner() {
                    this.scannerOpen = true;
                    const statusEl = document.getElementById('qr-camera-status');
                    const readerEl = document.getElementById('qr-reader');
                    const errorEl = document.getElementById('qr-scan-error');
                    if (errorEl) { errorEl.textContent = ''; errorEl.classList.add('hidden'); }
                    if (statusEl) statusEl.textContent = 'Menyiapkan kamera...';
                    if (readerEl) readerEl.innerHTML = '';
                    if (!window.QrScanner) {
                        this.moduleMissing = true;
                        if (statusEl) statusEl.textContent = 'Kamera tidak tersedia — gunakan upload / manual.';
                        if (errorEl) {
                            errorEl.textContent = 'Modul pemindai gagal dimuat (scan.js belum ter-build). Jalankan "npm run dev" / "npm run build".';
                            errorEl.classList.remove('hidden');
                        }
                        return;
                    }
                    this.$nextTick(() => {
                        window.QrScanner.open({
                            readerId: 'qr-reader',
                            selectId: 'qr-camera-select',
                            statusId: 'qr-camera-status',
                            errorId: 'qr-scan-error',
                            onDecode: (t) => {
                                window.QrScanner.close().finally(() => this.goTo(t));
                            },
                        });
                    });
                },
                closeScanner() {
                    if (window.QrScanner) {
                        window.QrScanner.close().finally(() => { this.scannerOpen = false; });
                    } else {
                        this.scannerOpen = false;
                    }
                },
                async handleFile(event) {
                    const file = event.target.files && event.target.files[0];
                    if (!file) return;
                    this.fileError = '';
                    if (!window.QrScanner) {
                        this.fileError = 'Modul pemindai belum dimuat. Gunakan input manual.';
                        return;
                    }
                    this.uploading = true;
                    try {
                        const raw = await window.QrScanner.scanFile(file, 'qr-reader');
                        this.goTo(raw);
                    } catch (e) {
                        this.fileError = 'QR tidak terbaca dari gambar. Pastikan foto jelas atau ketik token manual.';
                    } finally {
                        this.uploading = false;
                        try { event.target.value = ''; } catch (e) {}
                    }
                },
                normaliseManual(event) {
                    const input = document.getElementById('qr-manual-token');
                    if (input) input.value = this.normalise(input.value);
                },
            };
        }
    </script>
    <style>
        .qr-viewfinder video { width: 100% !important; min-height: 240px; object-fit: cover; border-radius: .75rem; }
        .qr-viewfinder canvas { display: none; }
    </style>

    @vite(['resources/js/scan.js'])
</x-app-layout>
