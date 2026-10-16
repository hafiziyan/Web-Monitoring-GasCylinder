<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Detail Audit #{{ $auditLog->id }}</h2>
            <a href="{{ route('audit-logs.index') }}"
                class="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Kembali</a>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="w-full space-y-6 px-4 sm:px-6 lg:px-8">
            <div class="rounded-lg border border-gray-200 bg-white shadow-sm">
                <div class="border-b border-gray-200 px-5 py-4">
                    <h3 class="text-sm font-semibold text-gray-800">Informasi Audit</h3>
                </div>
                <dl class="divide-y divide-gray-100 px-5 text-sm">
                    <div class="flex justify-between gap-4 py-3"><dt class="text-gray-500">ID</dt><dd class="font-medium text-gray-900">{{ $auditLog->id }}</dd></div>
                    <div class="flex justify-between gap-4 py-3"><dt class="text-gray-500">Timestamp</dt><dd class="font-medium text-gray-900">{{ $auditLog->created_at->format('d M Y H:i:s') }}</dd></div>
                    <div class="flex justify-between gap-4 py-3"><dt class="text-gray-500">User</dt><dd class="font-medium text-gray-900">{{ $auditLog->user?->name ?? '-' }}</dd></div>
                    <div class="flex justify-between gap-4 py-3"><dt class="text-gray-500">Action</dt><dd class="font-medium text-gray-900">{{ $auditLog->action }}</dd></div>
                    <div class="flex justify-between gap-4 py-3"><dt class="text-gray-500">Module</dt><dd class="font-medium text-gray-900">{{ $auditLog->module }}</dd></div>
                    <div class="flex justify-between gap-4 py-3"><dt class="text-gray-500">IP</dt><dd class="font-medium text-gray-900">{{ $auditLog->ip_address ?? '-' }}</dd></div>
                    <div class="flex justify-between gap-4 py-3"><dt class="text-gray-500">User-Agent</dt><dd class="max-w-md break-all text-right font-medium text-gray-900">{{ $auditLog->user_agent ?? '-' }}</dd></div>
                    <div class="flex justify-between gap-4 py-3"><dt class="text-gray-500">Description</dt><dd class="max-w-md text-right font-medium text-gray-900">{{ $auditLog->description }}</dd></div>
                </dl>
            </div>

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                <div class="rounded-lg border border-gray-200 bg-white shadow-sm">
                    <div class="border-b border-gray-200 px-5 py-4">
                        <h3 class="text-sm font-semibold text-gray-800">Old Values</h3>
                    </div>
                    <pre class="overflow-x-auto px-5 py-4 font-mono text-xs text-gray-700">{{ $auditLog->old_values ? json_encode($auditLog->old_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : '-' }}</pre>
                </div>
                <div class="rounded-lg border border-gray-200 bg-white shadow-sm">
                    <div class="border-b border-gray-200 px-5 py-4">
                        <h3 class="text-sm font-semibold text-gray-800">New Values</h3>
                    </div>
                    <pre class="overflow-x-auto px-5 py-4 font-mono text-xs text-gray-700">{{ $auditLog->new_values ? json_encode($auditLog->new_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : '-' }}</pre>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
