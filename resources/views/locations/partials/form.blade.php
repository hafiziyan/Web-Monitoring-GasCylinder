<div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3">
    <div>
        <x-input-label for="code" value="Code" />
        <x-text-input id="code" name="code" type="text" class="mt-1 block w-full"
            :value="old('code', $location->code ?? '')" required placeholder="cth: LOC-006" />
        <x-input-error :messages="$errors->get('code')" class="mt-2" />
    </div>
    <div>
        <x-input-label for="name" value="Name" />
        <x-text-input id="name" name="name" type="text" class="mt-1 block w-full"
            :value="old('name', $location->name ?? '')" required />
        <x-input-error :messages="$errors->get('name')" class="mt-2" />
    </div>
    <div class="sm:col-span-2 xl:col-span-3">
        <x-input-label for="description" value="Description" />
        <textarea id="description" name="description" rows="3"
            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-slate-500 focus:ring-slate-500">{{ old('description', $location->description ?? '') }}</textarea>
        <x-input-error :messages="$errors->get('description')" class="mt-2" />
    </div>
    <div class="sm:col-span-2 xl:col-span-3">
        <label class="flex items-center gap-2 text-sm text-gray-700">
            <input type="checkbox" name="is_active" value="1"
                @checked(old('is_active', ($location->is_active ?? true) ? '1' : '') == '1')
                class="rounded border-gray-300 text-slate-800 shadow-sm focus:ring-slate-500" />
            Aktif (dapat dipilih pada form tabung)
        </label>
        <x-input-error :messages="$errors->get('is_active')" class="mt-2" />
    </div>
</div>
