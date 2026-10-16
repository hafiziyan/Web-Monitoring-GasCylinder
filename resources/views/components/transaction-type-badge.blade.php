@props(['type'])
@php
    $value = $type instanceof \BackedEnum ? $type->value : $type;
    $labels = [
        'IN' => 'TABUNG MASUK',
        'OUT' => 'TABUNG KELUAR',
        'RETURN' => 'RETURN',
        'TRANSFER' => 'TRANSFER',
        'MAINTENANCE' => 'MAINTENANCE',
        'USE_START' => 'MULAI PAKAI',
        'USE_END' => 'SELESAI PAKAI',
    ];
    $styles = [
        'IN' => 'bg-emerald-50 text-emerald-700 ring-emerald-600/20',
        'OUT' => 'bg-blue-50 text-blue-700 ring-blue-600/20',
        'RETURN' => 'bg-teal-50 text-teal-700 ring-teal-600/20',
        'TRANSFER' => 'bg-purple-50 text-purple-700 ring-purple-600/20',
        'MAINTENANCE' => 'bg-amber-50 text-amber-700 ring-amber-600/20',
        'USE_START' => 'bg-sky-50 text-sky-700 ring-sky-600/20',
        'USE_END' => 'bg-teal-50 text-teal-700 ring-teal-600/20',
    ];
@endphp
<span class="inline-flex items-center rounded-md px-2 py-1 text-xs font-semibold ring-1 ring-inset {{ $styles[$value] ?? 'bg-gray-100 text-gray-600 ring-gray-500/20' }}">{{ $labels[$value] ?? $value }}</span>
