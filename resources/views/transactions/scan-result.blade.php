<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="leading-tight">
                <h2 class="text-xl font-bold text-slate-900">Hasil Scan</h2>
                <p class="mt-0.5 text-sm text-slate-500">Detail tabung dari QR yang dipindai.</p>
            </div>
            <a href="{{ route('transactions.scan') }}"
                class="inline-flex items-center gap-1.5 rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" /></svg>
                Scan Lagi
            </a>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="w-full space-y-5 px-4 sm:px-6 lg:px-8">
            @if (! $cylinder)
                <div class="rounded-2xl border border-red-200 bg-red-50 p-8 text-center shadow-sm">
                    <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-red-100 text-red-600">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" /></svg>
                    </div>
                    <p class="mt-3 text-sm font-bold text-red-800">QR Code tidak terdaftar.</p>
                    <p class="mt-1 text-xs text-red-600">Periksa cetakan QR atau ketik token manual dengan teliti.</p>
                    <a href="{{ route('transactions.scan') }}"
                        class="mt-4 inline-flex rounded-xl bg-slate-900 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-700">Kembali ke Scan</a>
                </div>
            @else
                <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 bg-gradient-to-r from-slate-900 to-slate-700 px-5 py-4">
                        <div>
                            <h3 class="text-sm font-bold text-white">{{ $cylinder->cylinder_code }}</h3>
                            <p class="font-mono text-xs text-slate-300">{{ $cylinder->qr_token }}</p>
                        </div>
                        <div class="flex items-center gap-2">
                            <x-cylinder-status-badge :status="$cylinder->status" />
                            <x-cylinder-condition-badge :condition="$cylinder->condition" />
                        </div>
                    </div>
                    <dl class="grid grid-cols-1 gap-x-6 px-5 py-2 text-sm sm:grid-cols-2">
                        <div class="flex justify-between gap-4 border-b border-slate-100 py-2.5"><dt class="text-slate-500">Serial Number</dt><dd class="font-medium text-slate-900">{{ $cylinder->serial_number ?? '-' }}</dd></div>
                        <div class="flex justify-between gap-4 border-b border-slate-100 py-2.5"><dt class="text-slate-500">Jenis Gas</dt><dd class="font-medium text-slate-900">{{ $cylinder->gasType->name }}</dd></div>
                        <div class="flex justify-between gap-4 border-b border-slate-100 py-2.5"><dt class="text-slate-500">Kapasitas</dt><dd class="font-medium text-slate-900">{{ $cylinder->capacity ?? '-' }} {{ $cylinder->unit }}</dd></div>
                        <div class="flex justify-between gap-4 border-b border-slate-100 py-2.5"><dt class="text-slate-500">Supplier</dt><dd class="font-medium text-slate-900">{{ $cylinder->supplier?->name ?? '-' }}</dd></div>
                        <div class="flex justify-between gap-4 border-b border-slate-100 py-2.5"><dt class="text-slate-500">Lokasi Saat Ini</dt><dd class="font-medium text-slate-900">{{ $cylinder->currentLocation?->name ?? 'Di vendor luar (OUTSIDE)' }}</dd></div>
                    </dl>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <h3 class="text-sm font-bold text-slate-800">Lanjut ke Transaksi</h3>
                    <p class="mt-0.5 text-xs text-slate-500">Tombol yang tidak valid untuk status saat ini otomatis dinonaktifkan.</p>
                    <div class="mt-3 grid grid-cols-2 gap-2.5 sm:grid-cols-3">
                        @if (in_array($cylinder->status->value, ['AVAILABLE', 'EMPTY'], true))
                            <a href="{{ route('transactions.in.create', ['cylinder' => $cylinder->cylinder_code]) }}" class="rounded-xl bg-slate-900 px-3 py-2.5 text-center text-sm font-semibold text-white transition hover:bg-slate-700">Tabung Masuk</a>
                        @else
                            <span title="Tabung berstatus {{ $cylinder->status->value }} tidak dapat masuk" class="cursor-not-allowed rounded-xl bg-slate-100 px-3 py-2.5 text-center text-sm font-medium text-slate-400">Tabung Masuk</span>
                        @endif
                        @if ($cylinder->status->value === 'AVAILABLE')
                            <a href="{{ route('transactions.use-start.create', ['cylinder' => $cylinder->cylinder_code]) }}" class="rounded-xl bg-sky-600 px-3 py-2.5 text-center text-sm font-semibold text-white transition hover:bg-sky-500">Mulai Pakai</a>
                        @endif
                        @if ($cylinder->status->value === 'IN_USE')
                            <a href="{{ route('transactions.use-end.create', ['cylinder' => $cylinder->cylinder_code]) }}" class="rounded-xl bg-teal-600 px-3 py-2.5 text-center text-sm font-semibold text-white transition hover:bg-teal-500">Selesai Pakai</a>
                        @endif
                        @if (in_array($cylinder->status->value, ['AVAILABLE', 'EMPTY'], true))
                            <a href="{{ route('transactions.out.create', ['cylinder' => $cylinder->cylinder_code]) }}" class="rounded-xl bg-slate-900 px-3 py-2.5 text-center text-sm font-semibold text-white transition hover:bg-slate-700">Tabung Keluar</a>
                        @else
                            <span title="Tabung berstatus {{ $cylinder->status->value }} tidak dapat dikeluarkan" class="cursor-not-allowed rounded-xl bg-slate-100 px-3 py-2.5 text-center text-sm font-medium text-slate-400">Tabung Keluar</span>
                        @endif
                        @if ($cylinder->status->value === 'OUTSIDE')
                            <a href="{{ route('transactions.return.create', ['cylinder' => $cylinder->cylinder_code]) }}" class="rounded-xl bg-violet-600 px-3 py-2.5 text-center text-sm font-semibold text-white transition hover:bg-violet-500">Tabung Kembali</a>
                        @endif
                        @if (in_array($cylinder->status->value, ['AVAILABLE', 'EMPTY'], true))
                            <a href="{{ route('transactions.transfer.create', ['cylinder' => $cylinder->cylinder_code]) }}" class="rounded-xl bg-blue-600 px-3 py-2.5 text-center text-sm font-semibold text-white transition hover:bg-blue-500">Transfer</a>
                        @else
                            <span title="Tabung berstatus {{ $cylinder->status->value }} tidak dapat dipindahkan" class="cursor-not-allowed rounded-xl bg-slate-100 px-3 py-2.5 text-center text-sm font-medium text-slate-400">Transfer</span>
                        @endif
                        @if (in_array($cylinder->status->value, ['AVAILABLE', 'EMPTY'], true))
                            <a href="{{ route('transactions.maintenance.create', ['cylinder' => $cylinder->cylinder_code]) }}" class="rounded-xl bg-amber-500 px-3 py-2.5 text-center text-sm font-semibold text-white transition hover:bg-amber-400">Maintenance</a>
                        @endif
                        @if ($cylinder->status->value === 'MAINTENANCE')
                            <a href="{{ route('transactions.maintenance.complete', ['cylinder' => $cylinder->cylinder_code]) }}" class="rounded-xl bg-emerald-600 px-3 py-2.5 text-center text-sm font-semibold text-white transition hover:bg-emerald-500">Maintenance Selesai</a>
                        @endif
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
