<x-app-layout>
    <x-slot name="header">
        <div class="leading-tight">
            <h2 class="font-semibold text-xl text-gray-800">Laporan Transaksi</h2>
            <p class="mt-1 text-sm text-gray-600">Riwayat pergerakan dan aktivitas seluruh tabung.</p>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="w-full space-y-4 px-4 sm:px-6 lg:px-8">
            {{-- Summary mengikuti filter aktif --}}
            <div class="grid grid-cols-2 gap-4 md:grid-cols-4 xl:grid-cols-8">
                <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
                    <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Total Transaksi</p>
                    <p class="mt-1 text-2xl font-bold text-gray-900" data-test="summary-total">{{ $summary['total'] }}</p>
                </div>
                <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
                    <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Tabung Masuk</p>
                    <p class="mt-1 text-2xl font-bold text-emerald-600" data-test="summary-in">{{ $summary['in'] }}</p>
                </div>
                <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
                    <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Tabung Keluar</p>
                    <p class="mt-1 text-2xl font-bold text-blue-600" data-test="summary-out">{{ $summary['out'] }}</p>
                </div>
                <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
                    <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Tabung Kembali</p>
                    <p class="mt-1 text-2xl font-bold text-teal-600" data-test="summary-return">{{ $summary['return'] }}</p>
                </div>
                <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
                    <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Transfer</p>
                    <p class="mt-1 text-2xl font-bold text-purple-600" data-test="summary-transfer">{{ $summary['transfer'] }}</p>
                </div>
                <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
                    <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Maintenance</p>
                    <p class="mt-1 text-2xl font-bold text-amber-600" data-test="summary-maintenance">{{ $summary['maintenance'] }}</p>
                </div>
                <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
                    <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Mulai Pakai</p>
                    <p class="mt-1 text-2xl font-bold text-sky-600" data-test="summary-use-start">{{ $summary['use_start'] }}</p>
                </div>
                <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
                    <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Selesai Pakai</p>
                    <p class="mt-1 text-2xl font-bold text-teal-600" data-test="summary-use-end">{{ $summary['use_end'] }}</p>
                </div>
            </div>

            <form method="GET" action="{{ route('reports.transactions') }}"
                class="grid grid-cols-1 gap-3 rounded-lg border border-gray-200 bg-white p-4 shadow-sm sm:grid-cols-2 lg:grid-cols-4">
                <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Cari kode, serial, token, gas, operator..."
                    class="rounded-md border-gray-300 shadow-sm focus:border-slate-500 focus:ring-slate-500 sm:col-span-2" />
                <select name="type" class="rounded-md border-gray-300 shadow-sm focus:border-slate-500 focus:ring-slate-500">
                    <option value="">Semua</option>
                    @foreach ($types as $type)
                        <option value="{{ $type->value }}" @selected(($filters['type'] ?? '') === $type->value)>{{ $type->value }}</option>
                    @endforeach
                </select>
                <select name="gas_type_id" class="rounded-md border-gray-300 shadow-sm focus:border-slate-500 focus:ring-slate-500">
                    <option value="">Semua Jenis Gas</option>
                    @foreach ($gasTypes as $gasType)
                        <option value="{{ $gasType->id }}" @selected(($filters['gas_type_id'] ?? '') == $gasType->id)>{{ $gasType->name }}</option>
                    @endforeach
                </select>
                <select name="from_location_id" class="rounded-md border-gray-300 shadow-sm focus:border-slate-500 focus:ring-slate-500">
                    <option value="">Semua Lokasi Asal</option>
                    @foreach ($locations as $location)
                        <option value="{{ $location->id }}" @selected(($filters['from_location_id'] ?? '') == $location->id)>{{ $location->name }}</option>
                    @endforeach
                </select>
                <select name="to_location_id" class="rounded-md border-gray-300 shadow-sm focus:border-slate-500 focus:ring-slate-500">
                    <option value="">Semua Lokasi Tujuan</option>
                    @foreach ($locations as $location)
                        <option value="{{ $location->id }}" @selected(($filters['to_location_id'] ?? '') == $location->id)>{{ $location->name }}</option>
                    @endforeach
                </select>
                <div>
                    <x-input-label for="date_from" value="Tanggal Dari" />
                    <input type="date" id="date_from" name="date_from" value="{{ $filters['date_from'] ?? '' }}"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-slate-500 focus:ring-slate-500" />
                </div>
                <div>
                    <x-input-label for="date_to" value="Tanggal Sampai" />
                    <input type="date" id="date_to" name="date_to" value="{{ $filters['date_to'] ?? '' }}"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-slate-500 focus:ring-slate-500" />
                </div>
                <div class="flex flex-wrap gap-2 sm:col-span-2 lg:col-span-4">
                    <button type="submit"
                        class="rounded-md bg-slate-800 px-4 py-2 text-sm font-medium text-white hover:bg-slate-700">Filter</button>
                    <a href="{{ route('reports.transactions') }}"
                        class="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Reset</a>
                    <a href="{{ route('reports.transactions.excel', request()->query()) }}"
                        class="rounded-md border border-emerald-600 px-4 py-2 text-sm font-medium text-emerald-700 hover:bg-emerald-50">Export Excel</a>
                    <a href="{{ route('reports.transactions.pdf', request()->query()) }}" target="_blank"
                        class="rounded-md border border-red-600 px-4 py-2 text-sm font-medium text-red-700 hover:bg-red-50">Export PDF</a>
                </div>
            </form>

            <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left font-medium uppercase tracking-wide text-gray-500">No</th>
                                <th class="px-4 py-3 text-left font-medium uppercase tracking-wide text-gray-500">Tanggal</th>
                                <th class="px-4 py-3 text-left font-medium uppercase tracking-wide text-gray-500">Kode Tabung</th>
                                <th class="px-4 py-3 text-left font-medium uppercase tracking-wide text-gray-500">Serial Number</th>
                                <th class="px-4 py-3 text-left font-medium uppercase tracking-wide text-gray-500">Jenis Gas</th>
                                <th class="px-4 py-3 text-left font-medium uppercase tracking-wide text-gray-500">Tipe Transaksi</th>
                                <th class="px-4 py-3 text-left font-medium uppercase tracking-wide text-gray-500">Lokasi Asal</th>
                                <th class="px-4 py-3 text-left font-medium uppercase tracking-wide text-gray-500">Lokasi Tujuan</th>
                                <th class="px-4 py-3 text-left font-medium uppercase tracking-wide text-gray-500">Tujuan</th>
                                <th class="px-4 py-3 text-left font-medium uppercase tracking-wide text-gray-500">Kondisi</th>
                                <th class="px-4 py-3 text-left font-medium uppercase tracking-wide text-gray-500">Sisa Gas</th>
                                <th class="px-4 py-3 text-left font-medium uppercase tracking-wide text-gray-500">Operator</th>
                                <th class="px-4 py-3 text-left font-medium uppercase tracking-wide text-gray-500">Catatan</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($transactions as $i => $trx)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3 text-gray-600">{{ $transactions->firstItem() + $i }}</td>
                                    <td class="whitespace-nowrap px-4 py-3 text-gray-600">{{ $trx->transaction_date->format('d M Y H:i') }}</td>
                                    <td class="whitespace-nowrap px-4 py-3 font-medium text-gray-900">{{ $trx->cylinder->cylinder_code }}</td>
                                    <td class="whitespace-nowrap px-4 py-3 text-gray-600">{{ $trx->cylinder->serial_number ?? '-' }}</td>
                                    <td class="whitespace-nowrap px-4 py-3 text-gray-600">{{ $trx->cylinder->gasType->name }}</td>
                                    <td class="whitespace-nowrap px-4 py-3"><x-transaction-type-badge :type="$trx->transaction_type" /></td>
                                    <td class="whitespace-nowrap px-4 py-3 text-gray-600">{{ $trx->fromLocation?->name ?? '-' }}</td>
                                    <td class="whitespace-nowrap px-4 py-3 text-gray-600">{{ $trx->toLocation?->name ?? '-' }}</td>
                                    <td class="whitespace-nowrap px-4 py-3 text-gray-600">{{ $trx->destination ?? '-' }}</td>
                                    <td class="whitespace-nowrap px-4 py-3 text-gray-600">{{ $trx->condition ?? '-' }}</td>
                                    <td class="whitespace-nowrap px-4 py-3 text-gray-600">{{ $trx->remaining_gas ?? '-' }}</td>
                                    <td class="whitespace-nowrap px-4 py-3 text-gray-600">{{ $trx->user->name }}</td>
                                    <td class="px-4 py-3 text-gray-600">{{ $trx->notes ?? '-' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="13" class="px-4 py-6 text-center text-gray-500">Data laporan tidak ditemukan.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="border-t border-gray-200 px-4 py-3">
                    {{ $transactions->links() }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
