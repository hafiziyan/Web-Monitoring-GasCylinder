<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Cetak QR</h2>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="w-full space-y-4 px-4 sm:px-6 lg:px-8">
            @include('cylinders.partials.flash')

            <form method="GET" action="{{ route('qr-codes.index') }}"
                class="grid grid-cols-1 gap-3 rounded-lg border border-gray-200 bg-white p-4 shadow-sm sm:grid-cols-2 lg:grid-cols-5">
                <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Cari code / serial..."
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
                    <a href="{{ route('qr-codes.index') }}"
                        class="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Reset</a>
                </div>
            </form>

            {{-- Form bulk-print dikosongkan dari pembungkus tabel agar form
                Generate per-baris (POST) tidak bersarang di dalamnya.
                Checkbox & tombol submit terhubung via atribut form="...". --}}
            <form id="bulk-print-form" method="GET" action="{{ route('qr-codes.print-selected') }}" target="_blank"></form>
            <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-200 px-4 py-3">
                    <p class="text-sm text-gray-600">Pilih cylinder lalu cetak label QR.</p>
                    <button type="submit" form="bulk-print-form"
                        class="rounded-md bg-slate-800 px-4 py-2 text-sm font-medium text-white hover:bg-slate-700">Cetak QR Terpilih</button>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left font-medium uppercase tracking-wide text-gray-500">Select</th>
                                <th class="px-4 py-3 text-left font-medium uppercase tracking-wide text-gray-500">Cylinder Code</th>
                                <th class="px-4 py-3 text-left font-medium uppercase tracking-wide text-gray-500">Serial Number</th>
                                <th class="px-4 py-3 text-left font-medium uppercase tracking-wide text-gray-500">Jenis Gas</th>
                                <th class="px-4 py-3 text-left font-medium uppercase tracking-wide text-gray-500">Status</th>
                                <th class="px-4 py-3 text-left font-medium uppercase tracking-wide text-gray-500">Lokasi</th>
                                <th class="px-4 py-3 text-left font-medium uppercase tracking-wide text-gray-500">QR Status</th>
                                <th class="px-4 py-3 text-left font-medium uppercase tracking-wide text-gray-500">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($cylinders as $cylinder)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3">
                                        @if ($cylinder->qr_token)
                                            <input type="checkbox" name="ids[]" value="{{ $cylinder->id }}" form="bulk-print-form"
                                                class="rounded border-gray-300 text-slate-800 shadow-sm focus:ring-slate-500" />
                                        @endif
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3 font-medium text-gray-900">{{ $cylinder->cylinder_code }}</td>
                                    <td class="whitespace-nowrap px-4 py-3 text-gray-600">{{ $cylinder->serial_number ?? '-' }}</td>
                                    <td class="whitespace-nowrap px-4 py-3 text-gray-600">{{ $cylinder->gasType->name }}</td>
                                    <td class="whitespace-nowrap px-4 py-3"><x-cylinder-status-badge :status="$cylinder->status" /></td>
                                    <td class="whitespace-nowrap px-4 py-3 text-gray-600">{{ $cylinder->currentLocation?->name ?? '-' }}</td>
                                    <td class="whitespace-nowrap px-4 py-3">
                                        @if ($cylinder->qr_token)
                                            <span class="inline-flex items-center rounded-md bg-emerald-50 px-2 py-1 text-xs font-semibold text-emerald-700 ring-1 ring-inset ring-emerald-600/20">Generated</span>
                                        @else
                                            <span class="inline-flex items-center rounded-md bg-gray-100 px-2 py-1 text-xs font-semibold text-gray-600 ring-1 ring-inset ring-gray-500/20">Not Generated</span>
                                        @endif
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3">
                                        <div class="flex items-center gap-2">
                                            @if ($cylinder->qr_token)
                                                <a href="{{ route('qr-codes.preview', $cylinder) }}" class="text-slate-600 hover:text-slate-900 hover:underline">Preview</a>
                                                <a href="{{ route('qr-codes.print', $cylinder) }}" target="_blank" class="text-blue-600 hover:text-blue-800 hover:underline">Cetak</a>
                                            @else
                                                <form method="POST" action="{{ route('qr-codes.generate', $cylinder) }}">
                                                    @csrf
                                                    <button type="submit" class="text-emerald-600 hover:text-emerald-800 hover:underline">Generate</button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="px-4 py-6 text-center text-gray-500">Tidak ada data tabung.</td>
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
