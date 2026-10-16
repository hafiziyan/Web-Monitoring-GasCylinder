<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Edit Jenis Gas {{ $gasType->code }}</h2>
    </x-slot>

    <div class="py-6">
        <div class="w-full space-y-4 px-4 sm:px-6 lg:px-8">
            @include('cylinders.partials.flash')

            <form method="POST" action="{{ route('gas-types.update', $gasType) }}"
                class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                @csrf
                @method('PUT')
                @include('gas-types.partials.form', ['gasType' => $gasType])
                <div class="mt-5 flex gap-2">
                    <button type="submit"
                        class="rounded-md bg-slate-800 px-4 py-2 text-sm font-medium text-white hover:bg-slate-700">Perbarui</button>
                    <a href="{{ route('gas-types.show', $gasType) }}"
                        class="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Batal</a>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
