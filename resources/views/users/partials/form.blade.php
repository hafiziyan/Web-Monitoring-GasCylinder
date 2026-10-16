{{-- Dipakai create & edit. $user null saat create. --}}
<div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
    <div>
        <x-input-label for="name" value="Nama Lengkap" class="font-semibold" />
        <x-text-input id="name" name="name" type="text" placeholder="cth: Operator Gudang A"
            class="mt-1.5 block w-full rounded-xl" :value="old('name', $user->name ?? '')" required autocomplete="name" />
        <x-input-error :messages="$errors->get('name')" class="mt-2" />
    </div>
    <div>
        <x-input-label for="email" value="Email (untuk login)" class="font-semibold" />
        <x-text-input id="email" name="email" type="email" placeholder="cth: operator@gascylinder.test"
            class="mt-1.5 block w-full rounded-xl" :value="old('email', $user->email ?? '')" required autocomplete="username" />
        <x-input-error :messages="$errors->get('email')" class="mt-2" />
    </div>
    <div>
        <x-input-label for="password" value="Password {{ isset($user) ? '(kosongkan jika tidak diubah)' : '' }}" class="font-semibold" />
        <x-text-input id="password" name="password" type="password" placeholder="Minimal 8 karakter"
            class="mt-1.5 block w-full rounded-xl" autocomplete="new-password" :required="! isset($user)" />
        <x-input-error :messages="$errors->get('password')" class="mt-2" />
    </div>
    <div>
        <x-input-label for="password_confirmation" value="Konfirmasi Password {{ isset($user) ? '(ikuti password baru)' : '' }}" class="font-semibold" />
        <x-text-input id="password_confirmation" name="password_confirmation" type="password" placeholder="Ulangi password"
            class="mt-1.5 block w-full rounded-xl" autocomplete="new-password" :required="! isset($user)" />
    </div>
    <div>
        <x-input-label for="role" value="Role" class="font-semibold" />
        <select id="role" name="role" required
            @if (isset($user) && $user->is(auth()->user())) disabled @endif
            class="mt-1.5 block w-full rounded-xl border-slate-300 shadow-sm focus:border-slate-500 focus:ring-slate-500 disabled:bg-slate-100">
            @foreach ($roles as $role)
                <option value="{{ $role->value }}" @selected(old('role', ($user->role ?? null)?->value) === $role->value)>{{ strtoupper($role->value) }}</option>
            @endforeach
        </select>
        @if (isset($user) && $user->is(auth()->user()))
            <input type="hidden" name="role" value="{{ $user->role->value }}" />
            <p class="mt-1.5 text-xs text-slate-500">Role akun sendiri tidak dapat diubah di sini.</p>
        @else
            <p class="mt-1.5 text-xs text-slate-500">Admin: semua akses. Operator: transaksi + lihat master data.</p>
        @endif
        <x-input-error :messages="$errors->get('role')" class="mt-2" />
    </div>
    <div>
        <x-input-label for="is_active" value="Status Akun" class="font-semibold" />
        <label class="mt-1.5 flex cursor-pointer items-center gap-3 rounded-xl border border-slate-300 px-4 py-2.5 shadow-sm transition hover:bg-slate-50 has-checked:border-emerald-400 has-checked:bg-emerald-50/50">
            <input id="is_active" name="is_active" type="checkbox" value="1"
                @checked(old('is_active', $user->is_active ?? true))
                @if (isset($user) && $user->is(auth()->user())) disabled @endif
                class="h-5 w-5 rounded border-slate-300 text-emerald-600 shadow-sm focus:ring-emerald-500 disabled:opacity-50" />
            <span class="text-sm font-medium text-slate-700">Akun aktif (bisa login)</span>
        </label>
        @if (isset($user) && $user->is(auth()->user()))
            <p class="mt-1.5 text-xs text-slate-500">Status akun sendiri tidak dapat diubah di sini.</p>
        @endif
        <x-input-error :messages="$errors->get('is_active')" class="mt-2" />
    </div>
</div>
