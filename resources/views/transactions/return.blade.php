<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center gap-3">
            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-violet-600 text-white shadow-sm">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 15 3 9m0 0 6-6M3 9h12a6 6 0 0 1 0 12h-3" /></svg>
            </span>
            <div class="min-w-0 leading-tight">
                <h2 class="text-lg font-bold text-slate-900 sm:text-xl">Tabung Kembali</h2>
                <p class="mt-0.5 truncate text-sm text-slate-500">Catat tabung yang kembali dari vendor luar ke lokasi internal.</p>
            </div>
            <div class="ml-auto hidden items-center gap-1.5 text-xs font-medium sm:flex">
                <span class="rounded-full bg-slate-900 px-3 py-1 text-white">1 • Pilih Tabung</span>
                <span class="text-slate-300">→</span>
                <span class="rounded-full bg-slate-100 px-3 py-1 text-slate-600 ring-1 ring-slate-200">2 • Info Kembali</span>
            </div>
        </div>
    </x-slot>

    <div class="py-4 sm:py-6">
        <x-transaction-form-page
            glyph="return"
            :has-cylinder="(bool) $selectedCylinder"
            :selected="$selectedDetail"
            :allowed="['OUTSIDE']"
            :search-url="route('transactions.cylinders.search')"
            :search-action="route('transactions.return.create')"
            :keyword="$searchKeyword"
            :show-last-destination="true"
            :form-action="route('transactions.return.store')"
            step2-title="Informasi Tabung Kembali"
            step2-desc="Lokasi penerima, kondisi, dan tanggal kembali."
            submit-label="Simpan Tabung Kembali"
            empty-hint="Pilih tabung berstatus OUTSIDE terlebih dahulu untuk membuka form."
        >
            <div>
                <x-input-label for="to_location_id" value="Lokasi Penerima (Internal)" class="font-semibold" />
                <select id="to_location_id" name="to_location_id" required
                    class="mt-1.5 block w-full rounded-xl border-slate-300 shadow-sm focus:border-slate-500 focus:ring-slate-500">
                    <option value="">-- Pilih lokasi --</option>
                    @foreach ($locations as $location)
                        <option value="{{ $location->id }}" @selected(old('to_location_id') == $location->id)>{{ $location->name }}</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('to_location_id')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="condition" value="Kondisi Tabung" class="font-semibold" />
                <select id="condition" name="condition" required
                    class="mt-1.5 block w-full rounded-xl border-slate-300 shadow-sm focus:border-slate-500 focus:ring-slate-500">
                    @foreach ($conditions as $condition)
                        <option value="{{ $condition->value }}" @selected(old('condition') === $condition->value)>{{ $condition->value }}</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('condition')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="remaining_gas" value="Sisa Gas (m³)" class="font-semibold" />
                <x-text-input id="remaining_gas" name="remaining_gas" type="number" step="0.01" min="0" placeholder="cth: 1.2"
                    class="mt-1.5 block w-full rounded-xl" :value="old('remaining_gas')" />
                <x-input-error :messages="$errors->get('remaining_gas')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="transaction_date" value="Tanggal Transaksi" class="font-semibold" />
                <x-text-input id="transaction_date" name="transaction_date" type="datetime-local"
                    class="mt-1.5 block w-full rounded-xl" :value="old('transaction_date', now()->format('Y-m-d\TH:i'))" required />
                <x-input-error :messages="$errors->get('transaction_date')" class="mt-2" />
            </div>
            <div class="sm:col-span-2">
                <x-input-label for="notes" value="Catatan (opsional)" class="font-semibold" />
                <textarea id="notes" name="notes" rows="3" placeholder="cth: Kembali dari depot isi ulang, segel utuh."
                    class="mt-1.5 block w-full rounded-xl border-slate-300 shadow-sm focus:border-slate-500 focus:ring-slate-500">{{ old('notes') }}</textarea>
                <x-input-error :messages="$errors->get('notes')" class="mt-2" />
            </div>
        </x-transaction-form-page>
    </div>
</x-app-layout>
