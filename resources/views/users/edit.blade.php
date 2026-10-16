<x-app-layout>
    <x-slot name="header">
        <div class="leading-tight">
            <h2 class="text-lg font-bold text-slate-900 sm:text-xl">Edit User: {{ $user->name }}</h2>
            <p class="mt-0.5 font-mono text-sm text-slate-500">{{ $user->email }} • {{ strtoupper($user->role->value) }}</p>
        </div>
    </x-slot>

    <div class="py-4 sm:py-6">
        <div class="w-full px-4 sm:px-6 lg:px-8">
            @include('cylinders.partials.flash')

            <form method="POST" action="{{ route('admin.users.update', $user) }}"
                class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                @csrf
                @method('PUT')
                <div class="border-b border-slate-100 bg-gradient-to-r from-slate-900 to-slate-700 px-5 py-4">
                    <h3 class="text-sm font-semibold text-white">Ubah Data Akun</h3>
                    <p class="text-xs text-slate-300">{{ $user->transactions_count ?? $user->transactions()->count() }} transaksi tercatat atas nama akun ini.</p>
                </div>
                <div class="p-4 sm:p-6">
                    @include('users.partials.form', ['user' => $user, 'roles' => $roles])
                    <div class="mt-6 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                        <a href="{{ route('admin.users.index') }}"
                            class="inline-flex items-center justify-center rounded-xl border border-slate-300 px-5 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">Batal</a>
                        <button type="submit"
                            class="inline-flex items-center justify-center gap-2 rounded-xl bg-slate-900 px-6 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-slate-700 active:scale-[.99]">
                            Simpan Perubahan
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
