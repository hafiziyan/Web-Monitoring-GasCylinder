<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center gap-3">
            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-emerald-600 text-white shadow-sm">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
            </span>
            <div class="min-w-0 leading-tight">
                <h2 class="text-lg font-bold text-slate-900 sm:text-xl">Maintenance Selesai</h2>
                <p class="mt-0.5 truncate text-sm text-slate-500">Tentukan status dan lokasi akhir tabung.</p>
            </div>
            <div class="ml-auto hidden items-center gap-1.5 text-xs font-medium sm:flex">
                <span class="rounded-full bg-slate-900 px-3 py-1 text-white">1 • Pilih Tabung</span>
                <span class="text-slate-300">→</span>
                <span class="rounded-full bg-slate-100 px-3 py-1 text-slate-600 ring-1 ring-slate-200">2 • Hasil Maintenance</span>
            </div>
        </div>
    </x-slot>

    <div class="py-4 sm:py-6">
        <x-transaction-form-page
            glyph="done"
            :has-cylinder="(bool) $selectedCylinder"
            :selected="$selectedDetail"
            :allowed="['MAINTENANCE']"
            :search-url="route('transactions.cylinders.search')"
            :search-action="route('transactions.maintenance.complete')"
            :keyword="$searchKeyword"
            :form-action="route('transactions.maintenance.complete.store')"
            :sync-condition="true"
            step2-title="Hasil Maintenance"
            step2-desc="Status akhir, lokasi penempatan, dan kondisi."
            submit-label="Selesaikan Maintenance"
            empty-hint="Pilih tabung berstatus MAINTENANCE terlebih dahulu untuk membuka form."
        >
            <div>
                <x-input-label for="to_location_id" value="Lokasi Setelah Maintenance" class="font-semibold" />
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
                <x-input-label for="final_status" value="Status Akhir" class="font-semibold" />
                <select id="final_status" name="final_status" required
                    class="mt-1.5 block w-full rounded-xl border-slate-300 shadow-sm focus:border-slate-500 focus:ring-slate-500">
                    @foreach ($finalStatuses as $status)
                        <option value="{{ $status->value }}" @selected(old('final_status', 'AVAILABLE') === $status->value)>{{ $status->value }}</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('final_status')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="condition" value="Kondisi" class="font-semibold" />
                <select id="condition" name="condition" required
                    class="mt-1.5 block w-full rounded-xl border-slate-300 shadow-sm focus:border-slate-500 focus:ring-slate-500">
                    @foreach ($conditions as $condition)
                        <option value="{{ $condition->value }}" @selected(old('condition', $selectedCylinder?->condition->value) === $condition->value)>{{ $condition->value }}</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('condition')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="remaining_gas" value="Sisa Gas (m³)" class="font-semibold" />
                <x-text-input id="remaining_gas" name="remaining_gas" type="number" step="0.01" min="0"
                    class="mt-1.5 block w-full rounded-xl" :value="old('remaining_gas', $lastRemainingGas)" />
                <x-input-error :messages="$errors->get('remaining_gas')" class="mt-2" />
            </div>
            <div class="sm:col-span-2">
                <x-input-label for="transaction_date" value="Tanggal Selesai" class="font-semibold" />
                <x-text-input id="transaction_date" name="transaction_date" type="datetime-local"
                    class="mt-1.5 block w-full rounded-xl sm:max-w-xs" :value="old('transaction_date', now()->format('Y-m-d\TH:i'))" required />
                <x-input-error :messages="$errors->get('transaction_date')" class="mt-2" />
            </div>
            <div class="sm:col-span-2">
                <x-input-label for="notes" value="Catatan (opsional)" class="font-semibold" />
                <textarea id="notes" name="notes" rows="3" placeholder="cth: Maintenance selesai, tabung siap digunakan."
                    class="mt-1.5 block w-full rounded-xl border-slate-300 shadow-sm focus:border-slate-500 focus:ring-slate-500">{{ old('notes') }}</textarea>
                <x-input-error :messages="$errors->get('notes')" class="mt-2" />
            </div>
        </x-transaction-form-page>
    </div>
</x-app-layout>
