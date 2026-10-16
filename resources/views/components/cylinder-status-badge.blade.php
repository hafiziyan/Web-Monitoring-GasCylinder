@props(['status'])
@php
    $styles = [
        'AVAILABLE' => 'bg-emerald-50 text-emerald-700 ring-emerald-600/20',
        'IN_USE' => 'bg-blue-50 text-blue-700 ring-blue-600/20',
        'EMPTY' => 'bg-gray-100 text-gray-600 ring-gray-500/20',
        'MAINTENANCE' => 'bg-amber-50 text-amber-700 ring-amber-600/20',
        'OUTSIDE' => 'bg-purple-50 text-purple-700 ring-purple-600/20',
        'INACTIVE' => 'bg-red-50 text-red-700 ring-red-600/20',
    ];
    $value = $status instanceof \BackedEnum ? $status->value : $status;
@endphp
<span class="inline-flex items-center rounded-md px-2 py-1 text-xs font-semibold ring-1 ring-inset {{ $styles[$value] ?? 'bg-gray-100 text-gray-600 ring-gray-500/20' }}">{{ $value }}</span>
