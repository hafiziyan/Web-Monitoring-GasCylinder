<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Gas Cylinder Monitoring System
        </h2>
        <p class="mt-1 text-sm text-gray-600">Selamat datang, {{ auth()->user()->name }}</p>
        <p class="text-sm text-gray-600">Role: {{ auth()->user()->role->value }}</p>
    </x-slot>

    <div class="py-6">
        <div class="w-full space-y-6 px-4 sm:px-6 lg:px-8">

            {{-- Quick actions --}}
            <div class="rounded-lg border border-gray-200 bg-white shadow-sm">
                <div class="border-b border-gray-200 px-5 py-4">
                    <h3 class="text-sm font-semibold text-gray-800">Quick Actions</h3>
                </div>
                <div class="grid grid-cols-2 gap-3 p-4 sm:grid-cols-4 lg:grid-cols-8">
                    <a href="{{ route('transactions.scan') }}"
                        class="group flex flex-col items-center gap-2.5 rounded-xl border border-slate-200 px-3 py-5 text-sm font-semibold text-slate-700 transition hover:-translate-y-0.5 hover:border-slate-300 hover:shadow-md">
                        <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-slate-900 text-white shadow-sm transition group-hover:scale-105">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M6.827 6.175A2.31 2.31 0 0 1 5.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 0 0-1.134-.175 2.31 2.31 0 0 1-1.64-1.055l-.822-1.316a2.192 2.192 0 0 0-1.736-1.039 48.774 48.774 0 0 0-5.232 0 2.192 2.192 0 0 0-1.736 1.039l-.821 1.316Z" />
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M16.5 12.75a4.5 4.5 0 1 1-9 0 4.5 4.5 0 0 1 9 0ZM18.75 10.5h.008v.008h-.008V10.5Z" />
                            </svg>
                        </span>
                        Scan QR
                    </a>
                    <a href="{{ route('transactions.in.create') }}"
                        class="group flex flex-col items-center gap-2.5 rounded-xl border border-slate-200 px-3 py-5 text-sm font-semibold text-slate-700 transition hover:-translate-y-0.5 hover:border-slate-300 hover:shadow-md">
                        <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-emerald-600 text-white shadow-sm transition group-hover:scale-105">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" />
                            </svg>
                        </span>
                        Tabung Masuk
                    </a>
                    <a href="{{ route('transactions.out.create') }}"
                        class="group flex flex-col items-center gap-2.5 rounded-xl border border-slate-200 px-3 py-5 text-sm font-semibold text-slate-700 transition hover:-translate-y-0.5 hover:border-slate-300 hover:shadow-md">
                        <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-rose-600 text-white shadow-sm transition group-hover:scale-105">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5m-13.5-9L12 3m0 0 4.5 4.5M12 3v13.5" />
                            </svg>
                        </span>
                        Tabung Keluar
                    </a>
                    <a href="{{ route('transactions.use-start.create') }}"
                        class="group flex flex-col items-center gap-2.5 rounded-xl border border-slate-200 px-3 py-5 text-sm font-semibold text-slate-700 transition hover:-translate-y-0.5 hover:border-slate-300 hover:shadow-md">
                        <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-sky-600 text-white shadow-sm transition group-hover:scale-105">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M5.25 5.653c0-.856.917-1.398 1.667-.986l11.54 6.348a1.125 1.125 0 0 1 0 1.971l-11.54 6.347a1.125 1.125 0 0 1-1.667-.985V5.653Z" />
                            </svg>
                        </span>
                        Mulai Pakai
                    </a>
                    <a href="{{ route('transactions.use-end.create') }}"
                        class="group flex flex-col items-center gap-2.5 rounded-xl border border-slate-200 px-3 py-5 text-sm font-semibold text-slate-700 transition hover:-translate-y-0.5 hover:border-slate-300 hover:shadow-md">
                        <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-teal-600 text-white shadow-sm transition group-hover:scale-105">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                            </svg>
                        </span>
                        Selesai Pakai
                    </a>
                    <a href="{{ route('transactions.return.create') }}"
                        class="group flex flex-col items-center gap-2.5 rounded-xl border border-slate-200 px-3 py-5 text-sm font-semibold text-slate-700 transition hover:-translate-y-0.5 hover:border-slate-300 hover:shadow-md">
                        <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-violet-600 text-white shadow-sm transition group-hover:scale-105">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M9 15 3 9m0 0 6-6M3 9h12a6 6 0 0 1 0 12h-3" />
                            </svg>
                        </span>
                        Tabung Kembali
                    </a>
                    <a href="{{ route('transactions.transfer.create') }}"
                        class="group flex flex-col items-center gap-2.5 rounded-xl border border-slate-200 px-3 py-5 text-sm font-semibold text-slate-700 transition hover:-translate-y-0.5 hover:border-slate-300 hover:shadow-md">
                        <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-blue-600 text-white shadow-sm transition group-hover:scale-105">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M7.5 21 3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 7.5m0 0L16.5 12M21 7.5H7.5" />
                            </svg>
                        </span>
                        Transfer Tabung
                    </a>
                    <a href="{{ route('transactions.maintenance.create') }}"
                        class="group flex flex-col items-center gap-2.5 rounded-xl border border-slate-200 px-3 py-5 text-sm font-semibold text-slate-700 transition hover:-translate-y-0.5 hover:border-slate-300 hover:shadow-md">
                        <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-amber-500 text-white shadow-sm transition group-hover:scale-105">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M11.42 15.17 17.25 21A2.652 2.652 0 0 0 21 17.25l-5.877-5.877M11.42 15.17l2.496-3.03c.317-.384.74-.626 1.208-.766M11.42 15.17l-4.655 5.653a2.548 2.548 0 1 1-3.586-3.586l6.837-5.63m5.108-.233c.55-.164 1.163-.188 1.743-.14a4.5 4.5 0 0 0 4.486-6.336l-3.276 3.277a3.004 3.004 0 0 1-2.25-2.25l3.276-3.276a4.5 4.5 0 0 0-6.336 4.486c.091 1.076-.071 2.264-.904 2.95l-.102.085m-1.745 1.437L5.909 7.5H4.5L2.25 3.75l1.5-1.5L7.5 4.5v1.409l4.26 4.26m-1.745 1.437 1.745-1.437m6.615 8.206L15.75 15.75M4.867 19.125h.008v.008h-.008v-.008Z" />
                            </svg>
                        </span>
                        Maintenance
                    </a>
                </div>
            </div>

            {{-- Statistik status tabung --}}
            <div class="grid grid-cols-2 gap-4 md:grid-cols-3 xl:grid-cols-6">
                <a href="{{ route('cylinders.index') }}" class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm transition hover:border-slate-400">
                    <div class="flex items-center justify-between">
                        <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Total Tabung</p>
                        <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 2.75h3M12 2.75v2.5" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9.75 5.25h4.5V7h-4.5z" />
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M7.5 8.5c0-1.105 2.015-2 4.5-2s4.5.895 4.5 2v11c0 1.105-2.015 2-4.5 2s-4.5-.895-4.5-2v-11Z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 8.5c0 1.105 2.015 2 4.5 2s4.5-.895 4.5-2" />
                        </svg>
                    </div>
                    <p class="mt-1 text-2xl font-bold text-gray-900" data-test="stat-total">{{ $stats['total'] }}</p>
                </a>
                <a href="{{ route('cylinders.index', ['status' => 'AVAILABLE']) }}" class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm transition hover:border-slate-400">
                    <div class="flex items-center justify-between">
                        <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Available</p>
                        <svg class="h-5 w-5 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                        </svg>
                    </div>
                    <p class="mt-1 text-2xl font-bold text-emerald-600" data-test="stat-available">{{ $stats['available'] }}</p>
                </a>
                <a href="{{ route('cylinders.index', ['status' => 'IN_USE']) }}" class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm transition hover:border-slate-400">
                    <div class="flex items-center justify-between">
                        <p class="text-xs font-medium uppercase tracking-wide text-gray-500">In Use</p>
                        <svg class="h-5 w-5 text-blue-500" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
                        </svg>
                    </div>
                    <p class="mt-1 text-2xl font-bold text-blue-600" data-test="stat-in_use">{{ $stats['in_use'] }}</p>
                </a>
                <a href="{{ route('cylinders.index', ['status' => 'EMPTY']) }}" class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm transition hover:border-slate-400">
                    <div class="flex items-center justify-between">
                        <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Empty</p>
                        <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="m20.25 7.5-.625 10.632a2.25 2.25 0 0 1-2.247 2.118H6.622a2.25 2.25 0 0 1-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125Z" />
                        </svg>
                    </div>
                    <p class="mt-1 text-2xl font-bold text-gray-500" data-test="stat-empty">{{ $stats['empty'] }}</p>
                </a>
                <a href="{{ route('cylinders.index', ['status' => 'MAINTENANCE']) }}" class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm transition hover:border-slate-400">
                    <div class="flex items-center justify-between">
                        <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Maintenance</p>
                        <svg class="h-5 w-5 text-amber-500" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M11.42 15.17 17.25 21A2.652 2.652 0 0 0 21 17.25l-5.877-5.877M11.42 15.17l2.496-3.03c.317-.384.74-.626 1.208-.766M11.42 15.17l-4.655 5.653a2.548 2.548 0 1 1-3.586-3.586l6.837-5.63m5.108-.233c.55-.164 1.163-.188 1.743-.14a4.5 4.5 0 0 0 4.486-6.336l-3.276 3.277a3.004 3.004 0 0 1-2.25-2.25l3.276-3.276a4.5 4.5 0 0 0-6.336 4.486c.091 1.076-.071 2.264-.904 2.95l-.102.085m-1.745 1.437L5.909 7.5H4.5L2.25 3.75l1.5-1.5L7.5 4.5v1.409l4.26 4.26m-1.745 1.437 1.745-1.437m6.615 8.206L15.75 15.75M4.867 19.125h.008v.008h-.008v-.008Z" />
                        </svg>
                    </div>
                    <p class="mt-1 text-2xl font-bold text-amber-600" data-test="stat-maintenance">{{ $stats['maintenance'] }}</p>
                </a>
                <a href="{{ route('cylinders.index', ['status' => 'OUTSIDE']) }}" class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm transition hover:border-slate-400">
                    <div class="flex items-center justify-between">
                        <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Outside</p>
                        <svg class="h-5 w-5 text-purple-500" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5m-13.5-9L12 3m0 0 4.5 4.5M12 3v13.5" />
                        </svg>
                    </div>
                    <p class="mt-1 text-2xl font-bold text-purple-600" data-test="stat-outside">{{ $stats['outside'] }}</p>
                </a>
            </div>

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                {{-- Distribusi jenis gas --}}
                <div class="rounded-lg border border-gray-200 bg-white shadow-sm">
                    <div class="border-b border-gray-200 px-5 py-4">
                        <h3 class="text-sm font-semibold text-gray-800">Distribusi Berdasarkan Jenis Gas</h3>
                    </div>
                    <ul class="space-y-3 px-5 py-4">
                        @forelse ($gasDistribution as $gas)
                            <li>
                                <div class="flex items-center justify-between text-sm">
                                    <span class="text-gray-700">{{ $gas->name }}</span>
                                    <span class="font-semibold text-slate-700">{{ $gas->cylinders_count }}</span>
                                </div>
                                <div class="mt-1 h-2 overflow-hidden rounded-full bg-gray-100">
                                    <div class="h-full rounded-full bg-slate-600" style="width: {{ $stats['total'] > 0 ? round($gas->cylinders_count / $stats['total'] * 100) : 0 }}%"></div>
                                </div>
                            </li>
                        @empty
                            <li class="py-1 text-sm text-gray-500">Belum ada data jenis gas.</li>
                        @endforelse
                    </ul>
                </div>

                {{-- Lokasi tabung saat ini --}}
                <div class="rounded-lg border border-gray-200 bg-white shadow-sm">
                    <div class="border-b border-gray-200 px-5 py-4">
                        <h3 class="text-sm font-semibold text-gray-800">Distribusi Tabung Berdasarkan Lokasi</h3>
                    </div>
                    <ul class="divide-y divide-gray-100 px-5">
                        @forelse ($locationSummary as $location)
                            <li class="flex items-center justify-between py-3">
                                <span class="text-sm text-gray-700">{{ $location->name }}</span>
                                <span class="rounded-full bg-slate-100 px-2.5 py-0.5 text-sm font-semibold text-slate-700">{{ $location->cylinders_count }}</span>
                            </li>
                        @empty
                            <li class="py-3 text-sm text-gray-500">Belum ada data lokasi.</li>
                        @endforelse
                    </ul>
                    <div class="border-t border-gray-200 bg-purple-50/50 px-5 py-3">
                        <div class="flex items-center justify-between text-sm">
                            <span class="font-medium text-gray-700">Tabung di Luar Perusahaan</span>
                            <span class="rounded-full bg-purple-100 px-2.5 py-0.5 font-semibold text-purple-700" data-test="stat-outside-location">{{ $stats['outside'] }}</span>
                        </div>
                        <p class="mt-1 text-xs text-gray-500">Tabung OUTSIDE tidak memiliki lokasi internal (current_location_id null).</p>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                {{-- Ringkasan kondisi --}}
                <div class="rounded-lg border border-gray-200 bg-white shadow-sm">
                    <div class="border-b border-gray-200 px-5 py-4">
                        <h3 class="text-sm font-semibold text-gray-800">Ringkasan Kondisi Fisik</h3>
                    </div>
                    <ul class="divide-y divide-gray-100 px-5">
                        <li class="flex items-center justify-between py-3">
                            <span class="inline-flex items-center rounded-md bg-emerald-50 px-2 py-1 text-xs font-semibold text-emerald-700 ring-1 ring-inset ring-emerald-600/20">GOOD</span>
                            <span class="text-sm font-semibold text-slate-700" data-test="stat-condition-good">{{ $conditions['good'] }}</span>
                        </li>
                        <li class="flex items-center justify-between py-3">
                            <span class="inline-flex items-center rounded-md bg-red-50 px-2 py-1 text-xs font-semibold text-red-700 ring-1 ring-inset ring-red-600/20">DAMAGED</span>
                            <span class="text-sm font-semibold text-slate-700" data-test="stat-condition-damaged">{{ $conditions['damaged'] }}</span>
                        </li>
                        <li class="flex items-center justify-between py-3">
                            <span class="inline-flex items-center rounded-md bg-amber-50 px-2 py-1 text-xs font-semibold text-amber-700 ring-1 ring-inset ring-amber-600/20">MAINTENANCE</span>
                            <span class="text-sm font-semibold text-slate-700" data-test="stat-condition-maintenance">{{ $conditions['maintenance'] }}</span>
                        </li>
                    </ul>
                </div>

                {{-- Aksi tersering --}}
                <div class="rounded-lg border border-gray-200 bg-white shadow-sm">
                    <div class="border-b border-gray-200 px-5 py-4">
                        <h3 class="text-sm font-semibold text-gray-800">Aksi Transaksi Tersering</h3>
                    </div>
                    <ul class="divide-y divide-gray-100 px-5">
                        @forelse ($topActions as $action)
                            <li class="flex items-center justify-between py-3">
                                <x-transaction-type-badge :type="$action->transaction_type" />
                                <span class="text-sm font-semibold text-slate-700">{{ $action->total }}x</span>
                            </li>
                        @empty
                            <li class="py-3 text-sm text-gray-500">Belum ada aktivitas transaksi.</li>
                        @endforelse
                    </ul>
                </div>
            </div>

            {{-- Aktivitas terbaru --}}
            <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
                <div class="flex items-center justify-between border-b border-gray-200 px-5 py-4">
                    <h3 class="text-sm font-semibold text-gray-800">Aktivitas Terbaru</h3>
                    <a href="{{ route('transactions.index') }}" class="text-sm font-medium text-slate-600 hover:text-slate-900 hover:underline">Lihat Riwayat</a>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-5 py-3 text-left font-medium uppercase tracking-wide text-gray-500">Waktu</th>
                                <th class="px-5 py-3 text-left font-medium uppercase tracking-wide text-gray-500">Cylinder Code</th>
                                <th class="px-5 py-3 text-left font-medium uppercase tracking-wide text-gray-500">Jenis Transaksi</th>
                                <th class="px-5 py-3 text-left font-medium uppercase tracking-wide text-gray-500">User</th>
                                <th class="px-5 py-3 text-left font-medium uppercase tracking-wide text-gray-500">Dari Lokasi</th>
                                <th class="px-5 py-3 text-left font-medium uppercase tracking-wide text-gray-500">Ke Lokasi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($latestActivities as $activity)
                                <tr class="hover:bg-gray-50">
                                    <td class="whitespace-nowrap px-5 py-3 text-gray-600">{{ $activity->transaction_date->format('d M Y H:i') }}</td>
                                    <td class="whitespace-nowrap px-5 py-3 font-medium">
                                        <a href="{{ route('transactions.show', $activity) }}" class="text-slate-700 hover:text-slate-900 hover:underline">{{ $activity->cylinder->cylinder_code }}</a>
                                    </td>
                                    <td class="whitespace-nowrap px-5 py-3">
                                        <x-transaction-type-badge :type="$activity->transaction_type" />
                                    </td>
                                    <td class="whitespace-nowrap px-5 py-3 text-gray-600">{{ $activity->user->name }}</td>
                                    <td class="whitespace-nowrap px-5 py-3 text-gray-600">{{ $activity->fromLocation?->name ?? '-' }}</td>
                                    <td class="whitespace-nowrap px-5 py-3 text-gray-600">{{ $activity->toLocation?->name ?? ($activity->destination ?? '-') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-5 py-4 text-center text-gray-500">Belum ada aktivitas transaksi.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
