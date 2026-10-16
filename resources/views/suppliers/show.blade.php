<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Detail Supplier {{ $supplier->code }}</h2>
            <div class="flex gap-2">
                @can('update', $supplier)
                    <a href="{{ route('suppliers.edit', $supplier) }}"
                        class="rounded-md bg-slate-800 px-4 py-2 text-sm font-medium text-white hover:bg-slate-700">Edit</a>
                @endcan
                <a href="{{ route('suppliers.index') }}"
                    class="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Kembali</a>
            </div>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="w-full space-y-4 px-4 sm:px-6 lg:px-8">
            @include('cylinders.partials.flash')

            <div class="rounded-lg border border-gray-200 bg-white shadow-sm">
                <dl class="divide-y divide-gray-100 px-5 text-sm">
                    <div class="flex justify-between gap-4 py-3"><dt class="text-gray-500">Code</dt><dd class="font-medium text-gray-900">{{ $supplier->code }}</dd></div>
                    <div class="flex justify-between gap-4 py-3"><dt class="text-gray-500">Nama</dt><dd class="font-medium text-gray-900">{{ $supplier->name }}</dd></div>
                    <div class="flex justify-between gap-4 py-3"><dt class="text-gray-500">Contact Person</dt><dd class="font-medium text-gray-900">{{ $supplier->contact_person ?? '-' }}</dd></div>
                    <div class="flex justify-between gap-4 py-3"><dt class="text-gray-500">Phone</dt><dd class="font-medium text-gray-900">{{ $supplier->phone ?? '-' }}</dd></div>
                    <div class="flex justify-between gap-4 py-3"><dt class="text-gray-500">Address</dt><dd class="text-right font-medium text-gray-900">{{ $supplier->address ?? '-' }}</dd></div>
                    <div class="flex items-center justify-between gap-4 py-3"><dt class="text-gray-500">Status</dt><dd><x-active-badge :active="$supplier->is_active" /></dd></div>
                    <div class="flex justify-between gap-4 py-3"><dt class="text-gray-500">Jumlah Tabung</dt><dd class="font-medium text-gray-900">{{ $supplier->cylinders_count }}</dd></div>
                </dl>
            </div>
        </div>
    </div>
</x-app-layout>
