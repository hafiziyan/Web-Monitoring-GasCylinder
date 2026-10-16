{{-- Dipakai create & edit. $cylinder null saat create; $readonly true saat edit. --}}
<div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3">
    <div>
        <x-input-label for="cylinder_code" value="Cylinder Code" />
        <x-text-input id="cylinder_code" name="cylinder_code" type="text" class="mt-1 block w-full"
            :value="old('cylinder_code', $cylinder->cylinder_code ?? '')" required
            :readonly="($readonly ?? false)" :disabled="($readonly ?? false)" />
        <x-input-error :messages="$errors->get('cylinder_code')" class="mt-2" />
    </div>
    <div>
        <x-input-label for="serial_number" value="Serial Number" />
        <x-text-input id="serial_number" name="serial_number" type="text" class="mt-1 block w-full"
            :value="old('serial_number', $cylinder->serial_number ?? '')" />
        <x-input-error :messages="$errors->get('serial_number')" class="mt-2" />
    </div>
    <div>
        <x-input-label for="gas_type_id" value="Jenis Gas" />
        <select id="gas_type_id" name="gas_type_id" required
            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-slate-500 focus:ring-slate-500">
            <option value="">-- Pilih --</option>
            @foreach ($gasTypes as $gasType)
                <option value="{{ $gasType->id }}" @selected(old('gas_type_id', $cylinder->gas_type_id ?? '') == $gasType->id)>{{ $gasType->name }} ({{ $gasType->code }})</option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('gas_type_id')" class="mt-2" />
    </div>
    <div>
        <x-input-label for="supplier_id" value="Supplier" />
        <select id="supplier_id" name="supplier_id"
            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-slate-500 focus:ring-slate-500">
            <option value="">-- Pilih --</option>
            @foreach ($suppliers as $supplier)
                <option value="{{ $supplier->id }}" @selected(old('supplier_id', $cylinder->supplier_id ?? '') == $supplier->id)>{{ $supplier->name }}</option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('supplier_id')" class="mt-2" />
    </div>
    <div>
        <x-input-label for="capacity" value="Capacity" />
        <x-text-input id="capacity" name="capacity" type="number" step="0.01" min="0" class="mt-1 block w-full"
            :value="old('capacity', $cylinder->capacity ?? '')" />
        <x-input-error :messages="$errors->get('capacity')" class="mt-2" />
    </div>
    <div>
        <x-input-label for="unit" value="Unit" />
        <x-text-input id="unit" name="unit" type="text" class="mt-1 block w-full"
            :value="old('unit', $cylinder->unit ?? 'm3')" required />
        <x-input-error :messages="$errors->get('unit')" class="mt-2" />
    </div>
    @if ($readonly ?? false)
        <div>
            <x-input-label value="Status (berubah via transaksi)" />
            <p class="mt-1 block w-full rounded-md border border-gray-200 bg-gray-50 px-3 py-2 text-sm font-semibold text-gray-700">{{ ($cylinder->status ?? null)?->value ?? '-' }}</p>
        </div>
    @else
        <div>
            <x-input-label for="status" value="Status" />
            <select id="status" name="status" required
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-slate-500 focus:ring-slate-500">
                @foreach ($statuses as $status)
                    @if (in_array($status->value, ['AVAILABLE', 'EMPTY'], true))
                        <option value="{{ $status->value }}" @selected(old('status', ($cylinder->status ?? null)?->value ?? 'AVAILABLE') === $status->value)>{{ $status->value }}</option>
                    @endif
                @endforeach
            </select>
            <x-input-error :messages="$errors->get('status')" class="mt-2" />
        </div>
    @endif
    <div>
        <x-input-label for="condition" value="Condition" />
        <select id="condition" name="condition" required
            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-slate-500 focus:ring-slate-500">
            @foreach ($conditions as $condition)
                <option value="{{ $condition->value }}" @selected(old('condition', ($cylinder->condition ?? null)?->value ?? 'GOOD') === $condition->value)>{{ $condition->value }}</option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('condition')" class="mt-2" />
    </div>
    @if ($readonly ?? false)
        <div>
            <x-input-label value="Lokasi Saat Ini (berubah via transaksi)" />
            <p class="mt-1 block w-full rounded-md border border-gray-200 bg-gray-50 px-3 py-2 text-sm font-semibold text-gray-700">{{ $cylinder->currentLocation?->name ?? 'Di vendor luar (OUTSIDE)' }}</p>
        </div>
    @else
        <div>
            <x-input-label for="current_location_id" value="Lokasi Saat Ini" />
            <select id="current_location_id" name="current_location_id" required
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-slate-500 focus:ring-slate-500">
                <option value="">-- Pilih --</option>
                @foreach ($locations as $location)
                    <option value="{{ $location->id }}" @selected(old('current_location_id', $cylinder->current_location_id ?? '') == $location->id)>{{ $location->name }}</option>
                @endforeach
            </select>
            <x-input-error :messages="$errors->get('current_location_id')" class="mt-2" />
        </div>
    @endif
    @if (! ($readonly ?? false))
        <div>
            <x-input-label value="QR Token" />
            <p class="mt-1 block w-full rounded-md border border-gray-200 bg-gray-50 px-3 py-2 text-sm text-gray-500">Dibuat otomatis oleh sistem saat disimpan.</p>
        </div>
    @endif
    <div class="sm:col-span-2 xl:col-span-3">
        <x-input-label for="notes" value="Notes" />
        <textarea id="notes" name="notes" rows="3"
            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-slate-500 focus:ring-slate-500">{{ old('notes', $cylinder->notes ?? '') }}</textarea>
        <x-input-error :messages="$errors->get('notes')" class="mt-2" />
    </div>
</div>
