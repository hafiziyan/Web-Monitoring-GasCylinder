@props(['active'])
<span class="inline-flex items-center rounded-md px-2 py-1 text-xs font-semibold ring-1 ring-inset {{ $active ? 'bg-emerald-50 text-emerald-700 ring-emerald-600/20' : 'bg-gray-100 text-gray-600 ring-gray-500/20' }}">{{ $active ? 'Aktif' : 'Nonaktif' }}</span>
