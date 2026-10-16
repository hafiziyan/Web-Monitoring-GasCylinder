<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Edit Tabung {{ $cylinder->cylinder_code }}</h2>
    </x-slot>

    <div class="py-6">
        <div class="w-full space-y-4 px-4 sm:px-6 lg:px-8">
            @include('cylinders.partials.flash')

            <form method="POST" action="{{ route('cylinders.update', $cylinder) }}"
                class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                @csrf
                @method('PUT')
                @include('cylinders.partials.form', ['cylinder' => $cylinder, 'readonly' => true])
                <div class="mt-5 flex gap-2">
                    <button type="submit"
                        class="rounded-md bg-slate-800 px-4 py-2 text-sm font-medium text-white hover:bg-slate-700">Perbarui</button>
                    <a href="{{ route('cylinders.show', $cylinder) }}"
                        class="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Batal</a>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
