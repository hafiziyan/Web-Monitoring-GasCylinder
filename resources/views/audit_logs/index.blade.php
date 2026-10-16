<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Audit Log</h2>
    </x-slot>

    <div class="py-6">
        <div class="w-full space-y-4 px-4 sm:px-6 lg:px-8">
            <form method="GET" action="{{ route('audit-logs.index') }}"
                class="grid grid-cols-1 gap-3 rounded-lg border border-gray-200 bg-white p-4 shadow-sm sm:grid-cols-2 lg:grid-cols-4">
                <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Cari deskripsi..."
                    class="rounded-md border-gray-300 shadow-sm focus:border-slate-500 focus:ring-slate-500 sm:col-span-2" />
                <select name="user_id" class="rounded-md border-gray-300 shadow-sm focus:border-slate-500 focus:ring-slate-500">
                    <option value="">Semua User</option>
                    @foreach ($users as $user)
                        <option value="{{ $user->id }}" @selected(($filters['user_id'] ?? '') == $user->id)>{{ $user->name }} ({{ $user->email }})</option>
                    @endforeach
                </select>
                <select name="action" class="rounded-md border-gray-300 shadow-sm focus:border-slate-500 focus:ring-slate-500">
                    <option value="">Semua Action</option>
                    @foreach ($actions as $action)
                        <option value="{{ $action }}" @selected(($filters['action'] ?? '') === $action)>{{ $action }}</option>
                    @endforeach
                </select>
                <select name="module" class="rounded-md border-gray-300 shadow-sm focus:border-slate-500 focus:ring-slate-500">
                    <option value="">Semua Module</option>
                    @foreach ($modules as $module)
                        <option value="{{ $module }}" @selected(($filters['module'] ?? '') === $module)>{{ $module }}</option>
                    @endforeach
                </select>
                <div>
                    <x-input-label for="date_from" value="Tanggal Dari" />
                    <input type="date" id="date_from" name="date_from" value="{{ $filters['date_from'] ?? '' }}"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-slate-500 focus:ring-slate-500" />
                </div>
                <div>
                    <x-input-label for="date_to" value="Tanggal Sampai" />
                    <input type="date" id="date_to" name="date_to" value="{{ $filters['date_to'] ?? '' }}"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-slate-500 focus:ring-slate-500" />
                </div>
                <div class="flex gap-2 sm:col-span-2 lg:col-span-2">
                    <button type="submit"
                        class="rounded-md bg-slate-800 px-4 py-2 text-sm font-medium text-white hover:bg-slate-700">Filter</button>
                    <a href="{{ route('audit-logs.index') }}"
                        class="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Reset</a>
                </div>
            </form>

            <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left font-medium uppercase tracking-wide text-gray-500">Tanggal</th>
                                <th class="px-4 py-3 text-left font-medium uppercase tracking-wide text-gray-500">User</th>
                                <th class="px-4 py-3 text-left font-medium uppercase tracking-wide text-gray-500">Action</th>
                                <th class="px-4 py-3 text-left font-medium uppercase tracking-wide text-gray-500">Module</th>
                                <th class="px-4 py-3 text-left font-medium uppercase tracking-wide text-gray-500">Description</th>
                                <th class="px-4 py-3 text-left font-medium uppercase tracking-wide text-gray-500">IP Address</th>
                                <th class="px-4 py-3 text-left font-medium uppercase tracking-wide text-gray-500">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($auditLogs as $log)
                                <tr class="hover:bg-gray-50">
                                    <td class="whitespace-nowrap px-4 py-3 text-gray-600">{{ $log->created_at->format('d M Y H:i') }}</td>
                                    <td class="whitespace-nowrap px-4 py-3 text-gray-600">{{ $log->user?->name ?? '-' }}</td>
                                    <td class="whitespace-nowrap px-4 py-3"><span class="inline-flex items-center rounded-md bg-slate-100 px-2 py-1 text-xs font-semibold text-slate-700">{{ $log->action }}</span></td>
                                    <td class="whitespace-nowrap px-4 py-3 text-gray-600">{{ $log->module }}</td>
                                    <td class="px-4 py-3 text-gray-600">{{ $log->description }}</td>
                                    <td class="whitespace-nowrap px-4 py-3 text-gray-600">{{ $log->ip_address ?? '-' }}</td>
                                    <td class="whitespace-nowrap px-4 py-3">
                                        <a href="{{ route('audit-logs.show', $log) }}" class="text-slate-600 hover:text-slate-900 hover:underline">Detail</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-4 py-6 text-center text-gray-500">Belum ada audit log.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="border-t border-gray-200 px-4 py-3">
                    {{ $auditLogs->links() }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
