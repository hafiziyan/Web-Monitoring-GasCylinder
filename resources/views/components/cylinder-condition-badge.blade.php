@props(['condition'])
@php
    $styles = [
        'GOOD' => 'bg-emerald-50 text-emerald-700 ring-emerald-600/20',
        'DAMAGED' => 'bg-red-50 text-red-700 ring-red-600/20',
        'MAINTENANCE' => 'bg-amber-50 text-amber-700 ring-amber-600/20',
    ];
    $value = $condition instanceof \BackedEnum ? $condition->value : $condition;
@endphp
<span class="inline-flex items-center rounded-md px-2 py-1 text-xs font-semibold ring-1 ring-inset {{ $styles[$value] ?? 'bg-gray-100 text-gray-600 ring-gray-500/20' }}">{{ $value }}</span>
