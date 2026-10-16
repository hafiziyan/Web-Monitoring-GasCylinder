<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center gap-3">
            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-slate-900 text-white shadow-sm">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" /></svg>
            </span>
            <div class="min-w-0 leading-tight">
                <h2 class="text-lg font-bold text-slate-900 sm:text-xl">Kelola Users</h2>
                <p class="mt-0.5 truncate text-sm text-slate-500">Buat akun, atur role, dan aktif/nonaktifkan pengguna.</p>
            </div>
            <a href="{{ route('admin.users.create') }}"
                class="ml-auto inline-flex items-center gap-1.5 rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-slate-700 active:scale-[.99]">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2.2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                Tambah User
            </a>
        </div>
    </x-slot>

    <div class="py-4 sm:py-6">
        <div class="w-full space-y-4 px-4 sm:px-6 lg:px-8">
            @include('cylinders.partials.flash')

            <form method="GET" action="{{ route('admin.users.index') }}"
                class="grid grid-cols-1 gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:grid-cols-2 xl:grid-cols-4">
                <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Cari nama / email..."
                    class="rounded-xl border-slate-300 shadow-sm focus:border-slate-500 focus:ring-slate-500 sm:col-span-2 xl:col-span-2" />
                <select name="role"
                    class="rounded-xl border-slate-300 shadow-sm focus:border-slate-500 focus:ring-slate-500">
                    <option value="">Semua Role</option>
                    <option value="admin" @selected(($filters['role'] ?? '') === 'admin')>Admin</option>
                    <option value="operator" @selected(($filters['role'] ?? '') === 'operator')>Operator</option>
                </select>
                <div class="flex gap-2">
                    <button type="submit"
                        class="flex-1 rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-700">Filter</button>
                    <a href="{{ route('admin.users.index') }}"
                        class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">Reset</a>
                </div>
            </form>

            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Nama</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Email</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Role</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Transaksi</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Status</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($users as $user)
                                <tr class="transition hover:bg-slate-50 {{ $user->is(auth()->user()) ? 'bg-amber-50/50' : '' }}">
                                    <td class="whitespace-nowrap px-4 py-3">
                                        <div class="flex items-center gap-2.5">
                                            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-slate-900 text-xs font-bold text-white">
                                                {{ strtoupper(substr($user->name, 0, 1)) }}
                                            </span>
                                            <span class="font-semibold text-slate-900">{{ $user->name }}
                                                @if ($user->is(auth()->user()))
                                                    <span class="ml-1 rounded-full bg-amber-100 px-2 py-0.5 text-[11px] font-bold text-amber-800">Anda</span>
                                                @endif
                                            </span>
                                        </div>
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3 text-slate-600">{{ $user->email }}</td>
                                    <td class="whitespace-nowrap px-4 py-3">
                                        <span class="rounded-full px-2.5 py-1 text-[11px] font-bold {{ $user->role->value === 'admin' ? 'bg-purple-100 text-purple-800' : 'bg-blue-100 text-blue-800' }}">
                                            {{ strtoupper($user->role->value) }}
                                        </span>
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3 text-slate-600">{{ $user->transactions_count }}</td>
                                    <td class="whitespace-nowrap px-4 py-3"><x-active-badge :active="$user->is_active" /></td>
                                    <td class="whitespace-nowrap px-4 py-3">
                                        <div class="flex flex-wrap items-center gap-x-3 gap-y-1">
                                            <a href="{{ route('admin.users.edit', $user) }}" class="font-medium text-blue-600 hover:text-blue-800 hover:underline">Edit</a>
                                            @unless ($user->is(auth()->user()))
                                                <form method="POST" action="{{ route('admin.users.toggle', $user) }}">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button type="submit" class="font-medium {{ $user->is_active ? 'text-amber-600 hover:text-amber-800' : 'text-emerald-600 hover:text-emerald-800' }} hover:underline">
                                                        {{ $user->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                                    </button>
                                                </form>
                                                <form method="POST" action="{{ route('admin.users.destroy', $user) }}"
                                                    onsubmit="return confirm('Hapus akun {{ $user->email }}?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="font-medium text-red-600 hover:text-red-800 hover:underline">Hapus</button>
                                                </form>
                                            @endunless
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-4 py-8 text-center text-slate-500">Tidak ada data user. Klik “Tambah User” untuk membuat akun baru.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="border-t border-slate-200 px-4 py-3">
                    {{ $users->links() }}
                </div>
            </div>
            <p class="text-xs text-slate-500">Admin: semua akses. Operator: transaksi + laporan + lihat master data (tidak bisa ubah master, QR, audit log, dan kelola user).</p>
        </div>
    </div>
</x-app-layout>
