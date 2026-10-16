<x-app-layout>
    <x-slot name="header">
        <div class="leading-tight">
            <h2 class="font-semibold text-xl text-gray-800">Laporan Tabung</h2>
            <p class="mt-1 text-sm text-gray-600">Status terkini seluruh tabung terdaftar.</p>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="w-full space-y-4 px-4 sm:px-6 lg:px-8">
            {{-- Summary mengikuti filter aktif --}}
            <div class="grid grid-cols-2 gap-4 md:grid-cols-4 xl:grid-cols-7">
                <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
                    <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Total</p>
                    <p class="mt-1 text-2xl font-bold text-gray-900" data-test="summary-total">{{ $summary['total'] }}</p>
                </div>
                <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
                    <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Available</p>
                    <p class="mt-1 text-2xl font-bold text-emerald-600" data-test="summary-available">{{ $summary['available'] }}</p>
                </div>
                <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
                    <p class="text-xs font-medium uppercase tracking-wide text-gray-500">In Use</p>
                    <p class="mt-1 text-2xl font-bold text-blue-600" data-test="summary-in_use">{{ $summary['in_use'] }}</p>
                </div>
                <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
                    <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Empty</p>
                    <p class="mt-1 text-2xl font-bold text-gray-500" data-test="summary-empty">{{ $summary['empty'] }}</p>
                </div>
                <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
                    <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Maintenance</p>
                    <p class="mt-1 text-2xl font-bold text-amber-600" data-test="summary-maintenance">{{ $summary['maintenance'] }}</p>
                </div>
                <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
                    <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Outside</p>
                    <p class="mt-1 text-2xl font-bold text-purple-600" data-test="summary-outside">{{ $summary['outside'] }}</p>
                </div>
                <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
                    <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Inactive</p>
                    <p class="mt-1 text-2xl font-bold text-red-600" data-test="summary-inactive">{{ $summary['inactive'] }}</p>
                </div>
            </div>

            <form method="GET" action="{{ route('reports.cylinders') }}"
                class="grid grid-cols-1 gap-3 rounded-lg border border-gray-200 bg-white p-4 shadow-sm sm:grid-cols-2 lg:grid-cols-4">
                <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Cari kode, serial, atau QR token..."
                    class="rounded-md border-gray-300 shadow-sm focus:border-slate-500 focus:ring-slate-500 sm:col-span-2" />
                <select name="status" class="rounded-md border-gray-300 shadow-sm focus:border-slate-500 focus:ring-slate-500">
                    <option value="">Semua Status</option>
                    @foreach ($statuses as $status)
                        <option value="{{ $status->value }}" @selected(($filters['status'] ?? '') === $status->value)>{{ $status->value }}</option>
                    @endforeach
                </select>
                <select name="gas_type_id" class="rounded-md border-gray-300 shadow-sm focus:border-slate-500 focus:ring-slate-500">
                    <option value="">Semua Jenis Gas</option>
                    @foreach ($gasTypes as $gasType)
                        <option value="{{ $gasType->id }}" @selected(($filters['gas_type_id'] ?? '') == $gasType->id)>{{ $gasType->name }}</option>
                    @endforeach
                </select>
                <select name="supplier_id" class="rounded-md border-gray-300 shadow-sm focus:border-slate-500 focus:ring-slate-500">
                    <option value="">Semua Supplier</option>
                    @foreach ($suppliers as $supplier)
                        <option value="{{ $supplier->id }}" @selected(($filters['supplier_id'] ?? '') == $supplier->id)>{{ $supplier->name }}</option>
                    @endforeach
                </select>
                <select name="condition" class="rounded-md border-gray-300 shadow-sm focus:border-slate-500 focus:ring-slate-500">
                    <option value="">Semua Kondisi</option>
                    @foreach ($conditions as $condition)
                        <option value="{{ $condition->value }}" @selected(($filters['condition'] ?? '') === $condition->value)>{{ $condition->value }}</option>
                    @endforeach
                </select>
                <select name="current_location_id" class="rounded-md border-gray-300 shadow-sm focus:border-slate-500 focus:ring-slate-500">
                    <option value="">Semua Lokasi</option>
                    @foreach ($locations as $location)
                        <option value="{{ $location->id }}" @selected(($filters['current_location_id'] ?? '') == $location->id)>{{ $location->name }}</option>
                    @endforeach
                </select>
                <div class="flex flex-wrap gap-2 sm:col-span-2 lg:col-span-3">
                    <button type="submit"
                        class="rounded-md bg-slate-800 px-4 py-2 text-sm font-medium text-white hover:bg-slate-700">Filter</button>
                    <a href="{{ route('reports.cylinders') }}"
                        class="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Reset</a>
                    <a href="{{ route('reports.cylinders.excel', request()->query()) }}"
                        class="rounded-md border border-emerald-600 px-4 py-2 text-sm font-medium text-emerald-700 hover:bg-emerald-50">Export Excel</a>
                    <a href="{{ route('reports.cylinders.pdf', request()->query()) }}" target="_blank"
                        class="rounded-md border border-red-600 px-4 py-2 text-sm font-medium text-red-700 hover:bg-red-50">Export PDF</a>
                </div>
            </form>

            <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left font-medium uppercase tracking-wide text-gray-500">Kode Tabung</th>
                                <th class="px-4 py-3 text-left font-medium uppercase tracking-wide text-gray-500">Serial Number</th>
                                <th class="px-4 py-3 text-left font-medium uppercase tracking-wide text-gray-500">Jenis Gas</th>
                                <th class="px-4 py-3 text-left font-medium uppercase tracking-wide text-gray-500">Kapasitas</th>
                                <th class="px-4 py-3 text-left font-medium uppercase tracking-wide text-gray-500">Supplier</th>
                                <th class="px-4 py-3 text-left font-medium uppercase tracking-wide text-gray-500">Status</th>
                                <th class="px-4 py-3 text-left font-medium uppercase tracking-wide text-gray-500">Kondisi</th>
                                <th class="px-4 py-3 text-left font-medium uppercase tracking-wide text-gray-500">Lokasi Saat Ini</th>
                                <th class="px-4 py-3 text-left font-medium uppercase tracking-wide text-gray-500">QR Token</th>
                                <th class="px-4 py-3 text-left font-medium uppercase tracking-wide text-gray-500">Update Terakhir</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($cylinders as $cylinder)
                                <tr class="hover:bg-gray-50">
                                    <td class="whitespace-nowrap px-4 py-3 font-medium text-gray-900">{{ $cylinder->cylinder_code }}</td>
                                    <td class="whitespace-nowrap px-4 py-3 text-gray-600">{{ $cylinder->serial_number ?? '-' }}</td>
                                    <td class="whitespace-nowrap px-4 py-3 text-gray-600">{{ $cylinder->gasType->name }}</td>
                                    <td class="whitespace-nowrap px-4 py-3 text-gray-600">{{ $cylinder->capacity ?? '-' }} {{ $cylinder->unit }}</td>
                                    <td class="whitespace-nowrap px-4 py-3 text-gray-600">{{ $cylinder->supplier?->name ?? '-' }}</td>
                                    <td class="whitespace-nowrap px-4 py-3"><x-cylinder-status-badge :status="$cylinder->status" /></td>
                                    <td class="whitespace-nowrap px-4 py-3"><x-cylinder-condition-badge :condition="$cylinder->condition" /></td>
                                    <td class="whitespace-nowrap px-4 py-3 text-gray-600">{{ $cylinder->currentLocation?->name ?? '-' }}</td>
                                    <td class="whitespace-nowrap px-4 py-3 font-mono text-gray-600">{{ $cylinder->qr_token ?? '-' }}</td>
                                    <td class="whitespace-nowrap px-4 py-3 text-gray-600">{{ $cylinder->updated_at->format('d M Y H:i') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="10" class="px-4 py-6 text-center text-gray-500">Data laporan tidak ditemukan.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="border-t border-gray-200 px-4 py-3">
                    {{ $cylinders->links() }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
