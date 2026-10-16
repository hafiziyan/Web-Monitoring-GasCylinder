<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Supplier</h2>
            @can('create', App\Models\Supplier::class)
                <a href="{{ route('suppliers.create') }}"
                    class="rounded-md bg-slate-800 px-4 py-2 text-sm font-medium text-white hover:bg-slate-700">Tambah Supplier</a>
            @endcan
        </div>
    </x-slot>

    <div class="py-6">
        <div class="w-full space-y-4 px-4 sm:px-6 lg:px-8">
            @include('cylinders.partials.flash')

            <form method="GET" action="{{ route('suppliers.index') }}"
                class="flex flex-col gap-3 rounded-lg border border-gray-200 bg-white p-4 shadow-sm sm:flex-row">
                <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Cari code / nama / contact..."
                    class="flex-1 rounded-md border-gray-300 shadow-sm focus:border-slate-500 focus:ring-slate-500" />
                <div class="flex gap-2">
                    <button type="submit"
                        class="flex-1 rounded-md bg-slate-800 px-4 py-2 text-sm font-medium text-white hover:bg-slate-700 sm:flex-none">Filter</button>
                    <a href="{{ route('suppliers.index') }}"
                        class="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Reset</a>
                </div>
            </form>

            <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left font-medium uppercase tracking-wide text-gray-500">Code</th>
                                <th class="px-4 py-3 text-left font-medium uppercase tracking-wide text-gray-500">Supplier Name</th>
                                <th class="px-4 py-3 text-left font-medium uppercase tracking-wide text-gray-500">Contact Person</th>
                                <th class="px-4 py-3 text-left font-medium uppercase tracking-wide text-gray-500">Phone</th>
                                <th class="px-4 py-3 text-left font-medium uppercase tracking-wide text-gray-500">Address</th>
                                <th class="px-4 py-3 text-left font-medium uppercase tracking-wide text-gray-500">Status</th>
                                <th class="px-4 py-3 text-left font-medium uppercase tracking-wide text-gray-500">Jumlah Tabung</th>
                                <th class="px-4 py-3 text-left font-medium uppercase tracking-wide text-gray-500">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($suppliers as $supplier)
                                <tr class="hover:bg-gray-50">
                                    <td class="whitespace-nowrap px-4 py-3 font-medium text-gray-900">{{ $supplier->code }}</td>
                                    <td class="whitespace-nowrap px-4 py-3 text-gray-600">{{ $supplier->name }}</td>
                                    <td class="whitespace-nowrap px-4 py-3 text-gray-600">{{ $supplier->contact_person ?? '-' }}</td>
                                    <td class="whitespace-nowrap px-4 py-3 text-gray-600">{{ $supplier->phone ?? '-' }}</td>
                                    <td class="px-4 py-3 text-gray-600">{{ $supplier->address ?? '-' }}</td>
                                    <td class="whitespace-nowrap px-4 py-3"><x-active-badge :active="$supplier->is_active" /></td>
                                    <td class="whitespace-nowrap px-4 py-3 text-gray-600">{{ $supplier->cylinders_count }}</td>
                                    <td class="whitespace-nowrap px-4 py-3">
                                        <div class="flex items-center gap-2">
                                            <a href="{{ route('suppliers.show', $supplier) }}" class="text-slate-600 hover:text-slate-900 hover:underline">Detail</a>
                                            @can('update', $supplier)
                                                <a href="{{ route('suppliers.edit', $supplier) }}" class="text-blue-600 hover:text-blue-800 hover:underline">Edit</a>
                                            @endcan
                                            @can('delete', $supplier)
                                                <form method="POST" action="{{ route('suppliers.destroy', $supplier) }}"
                                                    onsubmit="return confirm('Hapus supplier {{ $supplier->code }}?')">
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
                                    <td colspan="8" class="px-4 py-6 text-center text-gray-500">Tidak ada data supplier.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="border-t border-gray-200 px-4 py-3">
                    {{ $suppliers->links() }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
