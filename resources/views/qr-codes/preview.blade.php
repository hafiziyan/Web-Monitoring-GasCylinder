<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">QR Code {{ $cylinder->cylinder_code }}</h2>
            <div class="flex gap-2">
                @if ($cylinder->qr_token)
                    <a href="{{ route('qr-codes.print', $cylinder) }}" target="_blank"
                        class="rounded-md bg-slate-800 px-4 py-2 text-sm font-medium text-white hover:bg-slate-700">Cetak</a>
                @endif
                <a href="{{ route('qr-codes.index') }}"
                    class="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Kembali</a>
            </div>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="mx-auto max-w-md space-y-4 px-4 sm:px-6 lg:px-8">
            @include('cylinders.partials.flash')

            <div class="rounded-lg border border-gray-200 bg-white p-6 text-center shadow-sm">
                @if ($qrSvg)
                    <div class="mx-auto w-fit">{!! $qrSvg !!}</div>
                    <p class="mt-4 text-lg font-bold tracking-wide text-gray-900">{{ $cylinder->cylinder_code }}</p>
                    <p class="text-sm font-medium text-gray-600">{{ $cylinder->gasType->name }}</p>
                    <p class="text-sm text-gray-500">Serial: {{ $cylinder->serial_number ?? '-' }}</p>
                @else
                    <p class="text-sm text-gray-500">Cylinder belum memiliki QR token.</p>
                    <form method="POST" action="{{ route('qr-codes.generate', $cylinder) }}" class="mt-4">
                        @csrf
                        <button type="submit"
                            class="rounded-md bg-slate-800 px-4 py-2 text-sm font-medium text-white hover:bg-slate-700">Generate QR Token</button>
                    </form>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
