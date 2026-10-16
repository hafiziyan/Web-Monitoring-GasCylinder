@props([
    'glyph' => 'in',
    'hasCylinder' => false,
    'selected' => null,
    'allowed' => [],
    'searchUrl' => '',
    'searchAction' => '',
    'keyword' => '',
    'showLastDestination' => false,
    'formId' => 'transaction-form',
    'formAction' => '',
    'step2Title' => '',
    'step2Desc' => '',
    'submitLabel' => 'Simpan',
    'syncCondition' => false,
    'emptyHint' => 'Pilih tabung terlebih dahulu (scan / cari / daftar) untuk membuka form.',
])

@php
    $accents = [
        'in' => 'bg-emerald-600',
        'out' => 'bg-rose-600',
        'transfer' => 'bg-blue-600',
        'return' => 'bg-violet-600',
        'maintenance' => 'bg-amber-500',
        'done' => 'bg-emerald-600',
        'use-start' => 'bg-sky-600',
        'use-end' => 'bg-teal-600',
    ];
    $accent = $accents[$glyph] ?? 'bg-slate-900';
    $glyphs = [
        'in' => '<path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M12 3v13.5m0 0 4.5-4.5M12 16.5l-4.5-4.5" />',
        'out' => '<path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5m-13.5-9L12 3m0 0 4.5 4.5M12 3v13.5" />',
        'transfer' => '<path stroke-linecap="round" stroke-linejoin="round" d="M7.5 21 3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 7.5m0 0L16.5 12M21 7.5H7.5" />',
        'return' => '<path stroke-linecap="round" stroke-linejoin="round" d="M9 15 3 9m0 0 6-6M3 9h12a6 6 0 0 1 0 12h-3" />',
        'maintenance' => '<path stroke-linecap="round" stroke-linejoin="round" d="M11.42 15.17 17.25 21A2.652 2.652 0 0 0 21 17.25l-5.877-5.877M11.42 15.17l2.496-3.03c.317-.384.74-.626 1.208-.766M11.42 15.17l-4.655 5.653a2.548 2.548 0 1 1-3.586-3.586l6.837-5.63m5.108-.233c.55-.164 1.163-.188 1.743-.14a4.5 4.5 0 0 0 4.486-6.336l-3.276 3.277a3.004 3.004 0 0 1-2.25-2.25l3.276-3.276a4.5 4.5 0 0 0-6.336 4.486c.091 1.076-.071 2.264-.904 2.95l-.102.085m-1.745 1.437L5.909 7.5H4.5L2.25 3.75l1.5-1.5L7.5 4.5v1.409l4.26 4.26m-1.745 1.437 1.745-1.437m6.615 8.206L15.75 15.75M4.867 19.125h.008v.008h-.008v-.008Z" />',
        'done' => '<path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />',
        'use-start' => '<path stroke-linecap="round" stroke-linejoin="round" d="M5.25 5.653c0-.856.917-1.398 1.667-.986l11.54 6.348a1.125 1.125 0 0 1 0 1.971l-11.54 6.347a1.125 1.125 0 0 1-1.667-.985V5.653Z" />',
        'use-end' => '<path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />',
    ];
    $iconPaths = $glyphs[$glyph] ?? '';
@endphp

<div class="w-full px-4 sm:px-6 lg:px-8"
    x-data="{ hasCylinder: {{ $hasCylinder ? 'true' : 'false' }} }"
    @cylinder-selected.window="hasCylinder = true; if (window.innerWidth < 1280) { document.getElementById('transaction-step2')?.scrollIntoView({ behavior: 'smooth', block: 'start' }); }@if ($syncCondition) if ($event.detail) { const c = document.getElementById('condition'); if (c) c.value = $event.detail.condition; }@endif"
    @cylinder-cleared.window="hasCylinder = false">
    @include('cylinders.partials.flash')

    @if ($keyword && ! $hasCylinder)
        <div class="mb-4 flex items-start gap-2.5 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            <svg class="mt-0.5 h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" /></svg>
            <p>Tabung dengan kata kunci &ldquo;{{ $keyword }}&rdquo; tidak ditemukan. Coba scan ulang atau gunakan kata kunci lain.</p>
        </div>
    @endif

    {{-- 1 kolom (HP/tablet) menumpuk, 2 kolom (laptop ≥1280px) berdampingan --}}
    <div class="grid grid-cols-1 items-start gap-4 sm:gap-5 xl:grid-cols-5">
        {{-- Langkah 1: pilih tabung (sticky di laptop) --}}
        <div class="min-w-0 xl:col-span-2 xl:sticky xl:top-24">
            <x-cylinder-selector
                :selected="$selected"
                :allowed-statuses="$allowed"
                :search-url="$searchUrl"
                :search-action="$searchAction"
                :initial-keyword="$keyword"
                :show-last-destination="$showLastDestination"
                :input-name="'cylinder_id'"
                :form-id="$formId"
            />
        </div>

        {{-- Langkah 2: form transaksi --}}
        <div class="min-w-0 xl:col-span-3" id="transaction-step2">
            <div x-show="hasCylinder" x-cloak x-transition>
                <form id="{{ $formId }}" method="POST" action="{{ $formAction }}"
                    class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                    @csrf
                    <div class="flex items-center gap-3 border-b border-slate-100 bg-gradient-to-r from-slate-900 to-slate-700 px-5 py-4">
                        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-white/15 text-sm font-bold text-white ring-1 ring-white/30">2</span>
                        <div class="min-w-0">
                            <h3 class="text-sm font-semibold text-white">{{ $step2Title }}</h3>
                            <p class="truncate text-xs text-slate-300">{{ $step2Desc }}</p>
                        </div>
                        <span class="ml-auto hidden shrink-0 items-center rounded-md px-2 py-1 text-white {{ $accent }}">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">{!! $iconPaths !!}</svg>
                        </span>
                    </div>
                    <div class="p-4 sm:p-6">
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            {{ $slot }}
                        </div>
                        <div class="mt-6 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                            <a href="{{ route('dashboard') }}"
                                class="inline-flex items-center justify-center rounded-xl border border-slate-300 px-5 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">Batal</a>
                            <button type="submit"
                                class="inline-flex items-center justify-center gap-2 rounded-xl bg-slate-900 px-6 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-slate-700 focus:outline-none focus:ring-2 focus:ring-slate-500 focus:ring-offset-2 active:scale-[.99]">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2.2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                                {{ $submitLabel }}
                            </button>
                        </div>
                    </div>
                </form>
            </div>
            <div x-show="!hasCylinder"
                class="flex flex-col items-center gap-2 rounded-2xl border border-dashed border-slate-300 bg-white/70 px-6 py-10 text-center sm:py-14">
                <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-100 text-slate-400">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5A1.125 1.125 0 0 1 3.75 9.375v-4.5Zm9.75 0c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5a1.125 1.125 0 0 1-1.125-1.125v-4.5ZM3.75 14.625c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5a1.125 1.125 0 0 1-1.125-1.125v-4.5Zm9.75 0c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5a1.125 1.125 0 0 1-1.125-1.125v-4.5Z" /></svg>
                </span>
                <p class="max-w-xs text-sm text-slate-500">{{ $emptyHint }}</p>
            </div>
        </div>
    </div>
</div>
