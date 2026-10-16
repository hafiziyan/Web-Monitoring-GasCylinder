<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Detail Transaksi #{{ $transaction->id }}</h2>
            <a href="{{ route('transactions.index') }}"
                class="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Kembali</a>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="w-full space-y-6 px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                <div class="rounded-lg border border-gray-200 bg-white shadow-sm">
                    <div class="border-b border-gray-200 px-5 py-4">
                        <h3 class="text-sm font-semibold text-gray-800">Informasi Transaksi</h3>
                    </div>
                    <dl class="divide-y divide-gray-100 px-5 text-sm">
                        <div class="flex justify-between gap-4 py-3"><dt class="text-gray-500">ID Transaksi</dt><dd class="font-medium text-gray-900">{{ $transaction->id }}</dd></div>
                        <div class="flex justify-between gap-4 py-3"><dt class="text-gray-500">Tanggal</dt><dd class="font-medium text-gray-900">{{ $transaction->transaction_date->format('d M Y H:i') }}</dd></div>
                        <div class="flex items-center justify-between gap-4 py-3"><dt class="text-gray-500">Tipe Transaksi</dt><dd><x-transaction-type-badge :type="$transaction->transaction_type" /></dd></div>
                        <div class="flex justify-between gap-4 py-3"><dt class="text-gray-500">Operator</dt><dd class="font-medium text-gray-900">{{ $transaction->user->name }}</dd></div>
                        <div class="flex justify-between gap-4 py-3"><dt class="text-gray-500">Catatan</dt><dd class="text-right font-medium text-gray-900">{{ $transaction->notes ?? '-' }}</dd></div>
                    </dl>
                </div>

                <div class="rounded-lg border border-gray-200 bg-white shadow-sm">
                    <div class="border-b border-gray-200 px-5 py-4">
                        <h3 class="text-sm font-semibold text-gray-800">Informasi Tabung</h3>
                    </div>
                    <dl class="divide-y divide-gray-100 px-5 text-sm">
                        <div class="flex justify-between gap-4 py-3"><dt class="text-gray-500">Cylinder Code</dt><dd><a href="{{ route('cylinders.show', $transaction->cylinder) }}" class="font-medium text-blue-600 hover:text-blue-800 hover:underline">{{ $transaction->cylinder->cylinder_code }}</a></dd></div>
                        <div class="flex justify-between gap-4 py-3"><dt class="text-gray-500">Serial Number</dt><dd class="font-medium text-gray-900">{{ $transaction->cylinder->serial_number ?? '-' }}</dd></div>
                        <div class="flex justify-between gap-4 py-3"><dt class="text-gray-500">Jenis Gas</dt><dd class="font-medium text-gray-900">{{ $transaction->cylinder->gasType->name }}</dd></div>
                        <div class="flex justify-between gap-4 py-3"><dt class="text-gray-500">Kapasitas</dt><dd class="font-medium text-gray-900">{{ $transaction->cylinder->capacity ?? '-' }} {{ $transaction->cylinder->unit }}</dd></div>
                        <div class="flex justify-between gap-4 py-3"><dt class="text-gray-500">QR Token</dt><dd class="font-mono font-medium text-gray-900">{{ $transaction->cylinder->qr_token ?? '-' }}</dd></div>
                        <div class="flex justify-between gap-4 py-3"><dt class="text-gray-500">Kondisi</dt><dd class="font-medium text-gray-900">{{ $transaction->condition ?? '-' }}</dd></div>
                    </dl>
                </div>

                <div class="rounded-lg border border-gray-200 bg-white shadow-sm">
                    <div class="border-b border-gray-200 px-5 py-4">
                        <h3 class="text-sm font-semibold text-gray-800">Perpindahan</h3>
                    </div>
                    <dl class="divide-y divide-gray-100 px-5 text-sm">
                        <div class="flex justify-between gap-4 py-3"><dt class="text-gray-500">Lokasi Asal</dt><dd class="font-medium text-gray-900">{{ $transaction->fromLocation?->name ?? '-' }}</dd></div>
                        <div class="flex justify-between gap-4 py-3"><dt class="text-gray-500">Lokasi Tujuan</dt><dd class="font-medium text-gray-900">{{ $transaction->toLocation?->name ?? '-' }}</dd></div>
                        <div class="flex justify-between gap-4 py-3"><dt class="text-gray-500">Vendor / Isi Ulang</dt><dd class="font-medium text-gray-900">{{ $transaction->destination ?? '-' }}</dd></div>
                    </dl>
                </div>

                <div class="rounded-lg border border-gray-200 bg-white shadow-sm">
                    <div class="border-b border-gray-200 px-5 py-4">
                        <h3 class="text-sm font-semibold text-gray-800">Gas</h3>
                    </div>
                    <dl class="divide-y divide-gray-100 px-5 text-sm">
                        <div class="flex justify-between gap-4 py-3"><dt class="text-gray-500">Sisa Gas</dt><dd class="font-medium text-gray-900">{{ $transaction->remaining_gas ?? '-' }}</dd></div>
                        <div class="flex justify-between gap-4 py-3"><dt class="text-gray-500">Unit</dt><dd class="font-medium text-gray-900">{{ $transaction->cylinder->unit }}</dd></div>
                    </dl>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
