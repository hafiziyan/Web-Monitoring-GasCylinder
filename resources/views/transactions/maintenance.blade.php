<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center gap-3">
            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-amber-500 text-white shadow-sm">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M11.42 15.17 17.25 21A2.652 2.652 0 0 0 21 17.25l-5.877-5.877M11.42 15.17l2.496-3.03c.317-.384.74-.626 1.208-.766M11.42 15.17l-4.655 5.653a2.548 2.548 0 1 1-3.586-3.586l6.837-5.63m5.108-.233c.55-.164 1.163-.188 1.743-.14a4.5 4.5 0 0 0 4.486-6.336l-3.276 3.277a3.004 3.004 0 0 1-2.25-2.25l3.276-3.276a4.5 4.5 0 0 0-6.336 4.486c.091 1.076-.071 2.264-.904 2.95l-.102.085m-1.745 1.437L5.909 7.5H4.5L2.25 3.75l1.5-1.5L7.5 4.5v1.409l4.26 4.26m-1.745 1.437 1.745-1.437m6.615 8.206L15.75 15.75M4.867 19.125h.008v.008h-.008v-.008Z" /></svg>
            </span>
            <div class="min-w-0 leading-tight">
                <h2 class="text-lg font-bold text-slate-900 sm:text-xl">Maintenance Tabung</h2>
                <p class="mt-0.5 truncate text-sm text-slate-500">Kirim tabung ke lokasi maintenance internal.</p>
            </div>
            <div class="ml-auto hidden items-center gap-1.5 text-xs font-medium sm:flex">
                <span class="rounded-full bg-slate-900 px-3 py-1 text-white">1 • Pilih Tabung</span>
                <span class="text-slate-300">→</span>
                <span class="rounded-full bg-slate-100 px-3 py-1 text-slate-600 ring-1 ring-slate-200">2 • Informasi Maintenance</span>
            </div>
        </div>
    </x-slot>

    <div class="py-4 sm:py-6">
        <x-transaction-form-page
            glyph="maintenance"
            :has-cylinder="(bool) $selectedCylinder"
            :selected="$selectedDetail"
            :allowed="['AVAILABLE', 'EMPTY']"
            :search-url="route('transactions.cylinders.search')"
            :search-action="route('transactions.maintenance.create')"
            :keyword="$searchKeyword"
            :form-action="route('transactions.maintenance.store')"
            :sync-condition="true"
            step2-title="Informasi Maintenance"
            step2-desc="Jenis perbaikan, lokasi bengkel, dan kondisi."
            submit-label="Simpan Transaksi Maintenance"
        >
            <div>
                <x-input-label for="maintenance_type" value="Jenis Maintenance" class="font-semibold" />
                <select id="maintenance_type" name="maintenance_type" required
                    class="mt-1.5 block w-full rounded-xl border-slate-300 shadow-sm focus:border-slate-500 focus:ring-slate-500">
                    @foreach ($maintenanceTypes as $type)
                        <option value="{{ $type }}" @selected(old('maintenance_type') === $type)>{{ $type }}</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('maintenance_type')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="to_location_id" value="Lokasi Maintenance" class="font-semibold" />
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
                        <option value="{{ $condition->value }}" @selected(old('condition', $selectedCylinder?->condition->value) === $condition->value)>{{ $condition->value }}</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('condition')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="remaining_gas" value="Sisa Gas (m³)" class="font-semibold" />
                <x-text-input id="remaining_gas" name="remaining_gas" type="number" step="0.01" min="0" placeholder="cth: 0.5"
                    class="mt-1.5 block w-full rounded-xl" :value="old('remaining_gas')" />
                <x-input-error :messages="$errors->get('remaining_gas')" class="mt-2" />
            </div>
            <div class="sm:col-span-2">
                <x-input-label for="transaction_date" value="Tanggal Transaksi" class="font-semibold" />
                <x-text-input id="transaction_date" name="transaction_date" type="datetime-local"
                    class="mt-1.5 block w-full rounded-xl sm:max-w-xs" :value="old('transaction_date', now()->format('Y-m-d\TH:i'))" required />
                <x-input-error :messages="$errors->get('transaction_date')" class="mt-2" />
            </div>
            <div class="sm:col-span-2">
                <x-input-label for="notes" value="Catatan (opsional)" class="font-semibold" />
                <textarea id="notes" name="notes" rows="3" placeholder="cth: Valve bocor, perlu penggantian seal."
                    class="mt-1.5 block w-full rounded-xl border-slate-300 shadow-sm focus:border-slate-500 focus:ring-slate-500">{{ old('notes') }}</textarea>
                <x-input-error :messages="$errors->get('notes')" class="mt-2" />
            </div>
        </x-transaction-form-page>
    </div>
</x-app-layout>
