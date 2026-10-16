<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Detail Tabung {{ $cylinder->cylinder_code }}</h2>
            <div class="flex gap-2">
                @can('update', $cylinder)
                    <a href="{{ route('cylinders.edit', $cylinder) }}"
                        class="rounded-md bg-slate-800 px-4 py-2 text-sm font-medium text-white hover:bg-slate-700">Edit</a>
                @endcan
                @if ($cylinder->status->value === 'MAINTENANCE')
                    <a href="{{ route('transactions.maintenance.complete', ['cylinder' => $cylinder->cylinder_code]) }}"
                        class="rounded-md bg-emerald-700 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-600">Maintenance Selesai</a>
                @endif
                <a href="{{ route('cylinders.index') }}"
                    class="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Kembali</a>
            </div>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="w-full space-y-6 px-4 sm:px-6 lg:px-8">
            @include('cylinders.partials.flash')

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                <div class="rounded-lg border border-gray-200 bg-white shadow-sm">
                    <div class="border-b border-gray-200 px-5 py-4">
                        <h3 class="text-sm font-semibold text-gray-800">Identitas</h3>
                    </div>
                    <dl class="divide-y divide-gray-100 px-5 text-sm">
                        <div class="flex justify-between gap-4 py-3"><dt class="text-gray-500">Cylinder Code</dt><dd class="font-medium text-gray-900">{{ $cylinder->cylinder_code }}</dd></div>
                        <div class="flex justify-between gap-4 py-3"><dt class="text-gray-500">Serial Number</dt><dd class="font-medium text-gray-900">{{ $cylinder->serial_number ?? '-' }}</dd></div>
                        <div class="flex justify-between gap-4 py-3"><dt class="text-gray-500">QR Token</dt><dd class="font-mono font-medium text-gray-900">{{ $cylinder->qr_token }}</dd></div>
                    </dl>
                </div>

                <div class="rounded-lg border border-gray-200 bg-white shadow-sm">
                    <div class="border-b border-gray-200 px-5 py-4">
                        <h3 class="text-sm font-semibold text-gray-800">Spesifikasi</h3>
                    </div>
                    <dl class="divide-y divide-gray-100 px-5 text-sm">
                        <div class="flex justify-between gap-4 py-3"><dt class="text-gray-500">Jenis Gas</dt><dd class="font-medium text-gray-900">{{ $cylinder->gasType->name }} ({{ $cylinder->gasType->code }})</dd></div>
                        <div class="flex justify-between gap-4 py-3"><dt class="text-gray-500">Capacity</dt><dd class="font-medium text-gray-900">{{ $cylinder->capacity ?? '-' }} {{ $cylinder->unit }}</dd></div>
                        <div class="flex justify-between gap-4 py-3"><dt class="text-gray-500">Supplier</dt><dd class="font-medium text-gray-900">{{ $cylinder->supplier?->name ?? '-' }}</dd></div>
                    </dl>
                </div>

                <div class="rounded-lg border border-gray-200 bg-white shadow-sm">
                    <div class="border-b border-gray-200 px-5 py-4">
                        <h3 class="text-sm font-semibold text-gray-800">Kondisi</h3>
                    </div>
                    <dl class="divide-y divide-gray-100 px-5 text-sm">
                        <div class="flex items-center justify-between gap-4 py-3"><dt class="text-gray-500">Status</dt><dd><x-cylinder-status-badge :status="$cylinder->status" /></dd></div>
                        <div class="flex items-center justify-between gap-4 py-3"><dt class="text-gray-500">Condition</dt><dd><x-cylinder-condition-badge :condition="$cylinder->condition" /></dd></div>
                        <div class="flex justify-between gap-4 py-3"><dt class="text-gray-500">Lokasi Saat Ini</dt><dd class="font-medium text-gray-900">{{ $cylinder->currentLocation?->name ?? 'Di vendor luar (OUTSIDE)' }}</dd></div>
                    </dl>
                </div>

                <div class="rounded-lg border border-gray-200 bg-white shadow-sm">
                    <div class="border-b border-gray-200 px-5 py-4">
                        <h3 class="text-sm font-semibold text-gray-800">Catatan</h3>
                    </div>
                    <p class="px-5 py-4 text-sm text-gray-700">{{ $cylinder->notes ?? '-' }}</p>
                </div>
            </div>

            <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
                <div class="border-b border-gray-200 px-5 py-4">
                    <h3 class="text-sm font-semibold text-gray-800">Riwayat Transaksi</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-5 py-3 text-left font-medium uppercase tracking-wide text-gray-500">Tanggal</th>
                                <th class="px-5 py-3 text-left font-medium uppercase tracking-wide text-gray-500">Transaksi</th>
                                <th class="px-5 py-3 text-left font-medium uppercase tracking-wide text-gray-500">User</th>
                                <th class="px-5 py-3 text-left font-medium uppercase tracking-wide text-gray-500">Dari</th>
                                <th class="px-5 py-3 text-left font-medium uppercase tracking-wide text-gray-500">Ke</th>
                                <th class="px-5 py-3 text-left font-medium uppercase tracking-wide text-gray-500">Kondisi</th>
                                <th class="px-5 py-3 text-left font-medium uppercase tracking-wide text-gray-500">Sisa Gas</th>
                                <th class="px-5 py-3 text-left font-medium uppercase tracking-wide text-gray-500">Destination</th>
                                <th class="px-5 py-3 text-left font-medium uppercase tracking-wide text-gray-500">Catatan</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($history as $trx)
                                <tr class="hover:bg-gray-50">
                                    <td class="whitespace-nowrap px-5 py-3 text-gray-600">{{ $trx->transaction_date->format('d M Y H:i') }}</td>
                                    <td class="whitespace-nowrap px-5 py-3 font-medium text-gray-900">{{ $trx->transaction_type->value }}</td>
                                    <td class="whitespace-nowrap px-5 py-3 text-gray-600">{{ $trx->user->name }}</td>
                                    <td class="whitespace-nowrap px-5 py-3 text-gray-600">{{ $trx->fromLocation?->name ?? '-' }}</td>
                                    <td class="whitespace-nowrap px-5 py-3 text-gray-600">{{ $trx->toLocation?->name ?? '-' }}</td>
                                    <td class="whitespace-nowrap px-5 py-3 text-gray-600">{{ $trx->condition ?? '-' }}</td>
                                    <td class="whitespace-nowrap px-5 py-3 text-gray-600">{{ $trx->remaining_gas ?? '-' }}</td>
                                    <td class="whitespace-nowrap px-5 py-3 text-gray-600">{{ $trx->destination ?? '-' }}</td>
                                    <td class="px-5 py-3 text-gray-600">{{ $trx->notes ?? '-' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="px-5 py-4 text-center text-gray-500">Belum ada riwayat transaksi.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
