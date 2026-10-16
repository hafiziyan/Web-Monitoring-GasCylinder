<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Data Tabung</h2>
            @can('create', App\Models\Cylinder::class)
                <a href="{{ route('cylinders.create') }}"
                    class="rounded-md bg-slate-800 px-4 py-2 text-sm font-medium text-white hover:bg-slate-700">Tambah Tabung</a>
            @endcan
        </div>
    </x-slot>

    <div class="py-6">
        <div class="w-full space-y-4 px-4 sm:px-6 lg:px-8">
            @include('cylinders.partials.flash')

            <form method="GET" action="{{ route('cylinders.index') }}"
                class="grid grid-cols-1 gap-3 rounded-lg border border-gray-200 bg-white p-4 shadow-sm sm:grid-cols-2 lg:grid-cols-5">
                <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Cari tabung..."
                    class="rounded-md border-gray-300 shadow-sm focus:border-slate-500 focus:ring-slate-500 sm:col-span-2 lg:col-span-1" />
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
                <select name="current_location_id" class="rounded-md border-gray-300 shadow-sm focus:border-slate-500 focus:ring-slate-500">
                    <option value="">Semua Lokasi</option>
                    @foreach ($locations as $location)
                        <option value="{{ $location->id }}" @selected(($filters['current_location_id'] ?? '') == $location->id)>{{ $location->name }}</option>
                    @endforeach
                </select>
                <div class="flex gap-2 sm:col-span-2 lg:col-span-1">
                    <button type="submit"
                        class="flex-1 rounded-md bg-slate-800 px-4 py-2 text-sm font-medium text-white hover:bg-slate-700">Filter</button>
                    <a href="{{ route('cylinders.index') }}"
                        class="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Reset</a>
                </div>
            </form>

            <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left font-medium uppercase tracking-wide text-gray-500">Cylinder Code</th>
                                <th class="px-4 py-3 text-left font-medium uppercase tracking-wide text-gray-500">Serial Number</th>
                                <th class="px-4 py-3 text-left font-medium uppercase tracking-wide text-gray-500">Jenis Gas</th>
                                <th class="px-4 py-3 text-left font-medium uppercase tracking-wide text-gray-500">Kapasitas</th>
                                <th class="px-4 py-3 text-left font-medium uppercase tracking-wide text-gray-500">Status</th>
                                <th class="px-4 py-3 text-left font-medium uppercase tracking-wide text-gray-500">Kondisi</th>
                                <th class="px-4 py-3 text-left font-medium uppercase tracking-wide text-gray-500">Lokasi Saat Ini</th>
                                <th class="px-4 py-3 text-left font-medium uppercase tracking-wide text-gray-500">Supplier</th>
                                <th class="px-4 py-3 text-left font-medium uppercase tracking-wide text-gray-500">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($cylinders as $cylinder)
                                <tr class="hover:bg-gray-50">
                                    <td class="whitespace-nowrap px-4 py-3 font-medium text-gray-900">{{ $cylinder->cylinder_code }}</td>
                                    <td class="whitespace-nowrap px-4 py-3 text-gray-600">{{ $cylinder->serial_number ?? '-' }}</td>
                                    <td class="whitespace-nowrap px-4 py-3 text-gray-600">{{ $cylinder->gasType->name }}</td>
                                    <td class="whitespace-nowrap px-4 py-3 text-gray-600">{{ $cylinder->capacity ?? '-' }} {{ $cylinder->unit }}</td>
                                    <td class="whitespace-nowrap px-4 py-3"><x-cylinder-status-badge :status="$cylinder->status" /></td>
                                    <td class="whitespace-nowrap px-4 py-3"><x-cylinder-condition-badge :condition="$cylinder->condition" /></td>
                                    <td class="whitespace-nowrap px-4 py-3 text-gray-600">{{ $cylinder->currentLocation?->name ?? '-' }}</td>
                                    <td class="whitespace-nowrap px-4 py-3 text-gray-600">{{ $cylinder->supplier?->name ?? '-' }}</td>
                                    <td class="whitespace-nowrap px-4 py-3">
                                        <div class="flex items-center gap-2">
                                            <a href="{{ route('cylinders.show', $cylinder) }}" class="text-slate-600 hover:text-slate-900 hover:underline">Detail</a>
                                            @can('update', $cylinder)
                                                <a href="{{ route('cylinders.edit', $cylinder) }}" class="text-blue-600 hover:text-blue-800 hover:underline">Edit</a>
                                            @endcan
                                            @can('delete', $cylinder)
                                                <form method="POST" action="{{ route('cylinders.destroy', $cylinder) }}"
                                                    onsubmit="return confirm('Hapus tabung {{ $cylinder->cylinder_code }}?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="text-red-600 hover:text-red-800 hover:underline">Hapus</button>
                                                </form>
                                            @endcan
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="px-4 py-6 text-center text-gray-500">Tidak ada data tabung.</td>
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
