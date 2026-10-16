<x-app-layout>
    <x-slot name="header">
        <div class="leading-tight">
            <h2 class="font-semibold text-xl text-gray-800">Riwayat Transaksi</h2>
            <p class="mt-1 text-sm text-gray-600">Riwayat pergerakan dan aktivitas seluruh tabung.</p>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="w-full space-y-4 px-4 sm:px-6 lg:px-8">
            <form method="GET" action="{{ route('transactions.index') }}"
                class="grid grid-cols-1 gap-3 rounded-lg border border-gray-200 bg-white p-4 shadow-sm sm:grid-cols-2 lg:grid-cols-4">
                <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Cari kode tabung, serial number, atau QR token..."
                    class="rounded-md border-gray-300 shadow-sm focus:border-slate-500 focus:ring-slate-500 sm:col-span-2 lg:col-span-2" />
                <select name="type" class="rounded-md border-gray-300 shadow-sm focus:border-slate-500 focus:ring-slate-500">
                    <option value="">Semua Transaksi</option>
                    @foreach ($types as $type)
                        <option value="{{ $type->value }}" @selected(($filters['type'] ?? '') === $type->value)>
                            {{ $type->value === 'IN' ? 'Tabung Masuk' : ($type->value === 'OUT' ? 'Tabung Keluar' : ucfirst(strtolower($type->value))) }}
                        </option>
                    @endforeach
                </select>
                <select name="gas_type_id" class="rounded-md border-gray-300 shadow-sm focus:border-slate-500 focus:ring-slate-500">
                    <option value="">Semua Jenis Gas</option>
                    @foreach ($gasTypes as $gasType)
                        <option value="{{ $gasType->id }}" @selected(($filters['gas_type_id'] ?? '') == $gasType->id)>{{ $gasType->name }}</option>
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
                <div class="flex gap-2 sm:col-span-2 lg:col-span-4">
                    <button type="submit"
                        class="rounded-md bg-slate-800 px-4 py-2 text-sm font-medium text-white hover:bg-slate-700">Filter</button>
                    <a href="{{ route('transactions.index') }}"
                        class="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Reset Filter</a>
                </div>
            </form>

            <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left font-medium uppercase tracking-wide text-gray-500">Tanggal</th>
                                <th class="px-4 py-3 text-left font-medium uppercase tracking-wide text-gray-500">Kode Tabung</th>
                                <th class="px-4 py-3 text-left font-medium uppercase tracking-wide text-gray-500">Jenis Gas</th>
                                <th class="px-4 py-3 text-left font-medium uppercase tracking-wide text-gray-500">Tipe Transaksi</th>
                                <th class="px-4 py-3 text-left font-medium uppercase tracking-wide text-gray-500">Lokasi Asal</th>
                                <th class="px-4 py-3 text-left font-medium uppercase tracking-wide text-gray-500">Lokasi Tujuan</th>
                                <th class="px-4 py-3 text-left font-medium uppercase tracking-wide text-gray-500">Tujuan</th>
                                <th class="px-4 py-3 text-left font-medium uppercase tracking-wide text-gray-500">Kondisi</th>
                                <th class="px-4 py-3 text-left font-medium uppercase tracking-wide text-gray-500">Sisa Gas</th>
                                <th class="px-4 py-3 text-left font-medium uppercase tracking-wide text-gray-500">Operator</th>
                                <th class="px-4 py-3 text-left font-medium uppercase tracking-wide text-gray-500">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($transactions as $transaction)
                                <tr class="hover:bg-gray-50">
                                    <td class="whitespace-nowrap px-4 py-3 text-gray-600">{{ $transaction->transaction_date->format('d M Y H:i') }}</td>
                                    <td class="whitespace-nowrap px-4 py-3 font-medium text-gray-900">{{ $transaction->cylinder->cylinder_code }}</td>
                                    <td class="whitespace-nowrap px-4 py-3 text-gray-600">{{ $transaction->cylinder->gasType->name }}</td>
                                    <td class="whitespace-nowrap px-4 py-3"><x-transaction-type-badge :type="$transaction->transaction_type" /></td>
                                    <td class="whitespace-nowrap px-4 py-3 text-gray-600">{{ $transaction->fromLocation?->name ?? '-' }}</td>
                                    <td class="whitespace-nowrap px-4 py-3 text-gray-600">{{ $transaction->toLocation?->name ?? '-' }}</td>
                                    <td class="whitespace-nowrap px-4 py-3 text-gray-600">{{ $transaction->destination ?? '-' }}</td>
                                    <td class="whitespace-nowrap px-4 py-3 text-gray-600">{{ $transaction->condition ?? '-' }}</td>
                                    <td class="whitespace-nowrap px-4 py-3 text-gray-600">{{ $transaction->remaining_gas ?? '-' }}</td>
                                    <td class="whitespace-nowrap px-4 py-3 text-gray-600">{{ $transaction->user->name }}</td>
                                    <td class="whitespace-nowrap px-4 py-3">
                                        <a href="{{ route('transactions.show', $transaction) }}" class="text-slate-600 hover:text-slate-900 hover:underline">Detail</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="11" class="px-4 py-6 text-center text-gray-500">
                                        {{ request()->query() ? 'Tidak ada transaksi yang sesuai dengan filter.' : 'Belum ada transaksi.' }}
                                    </td>
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
