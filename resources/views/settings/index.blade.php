<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center gap-3">
            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-slate-900 text-white shadow-sm">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 0 1 1.37.49l1.296 2.247a1.125 1.125 0 0 1-.26 1.431l-1.003.827c-.293.24-.438.613-.431.992a6.759 6.759 0 0 1 0 .255c-.007.378.138.75.43.99l1.005.828c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 0 1-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.57 6.57 0 0 1-.22.128c-.331.183-.581.495-.644.869l-.213 1.28c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.02-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 0 1-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 0 1-1.369-.49l-1.297-2.247a1.125 1.125 0 0 1 .26-1.431l1.004-.827c.292-.24.437-.613.43-.992a6.932 6.932 0 0 1 0-.255c.007-.378-.138-.75-.43-.99l-1.004-.828a1.125 1.125 0 0 1-.26-1.43l1.297-2.247a1.125 1.125 0 0 1 1.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.087.22-.128.332-.183.582-.495.644-.869l.214-1.28Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" /></svg>
            </span>
            <div class="min-w-0 leading-tight">
                <h2 class="text-lg font-bold text-slate-900 sm:text-xl">Pengaturan</h2>
                <p class="mt-0.5 truncate text-sm text-slate-500">Perusahaan, jenis maintenance, dan tampilan data. Berlaku langsung setelah disimpan.</p>
            </div>
            <button type="submit" form="settings-form"
                class="ml-auto inline-flex items-center gap-1.5 rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-slate-700 active:scale-[.99]">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2.2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                Simpan Pengaturan
            </button>
        </div>
    </x-slot>

    <div class="py-4 sm:py-6">
        <div class="w-full px-4 sm:px-6 lg:px-8">
            @include('cylinders.partials.flash')

            <form id="settings-form" method="POST" action="{{ route('admin.settings.update') }}">
                @csrf
                @method('PUT')
                <div class="grid grid-cols-1 items-start gap-4 sm:gap-5 xl:grid-cols-2">
                    {{-- A. Informasi perusahaan --}}
                    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                        <div class="border-b border-slate-100 px-5 py-4">
                            <h3 class="text-sm font-bold text-slate-900">A. Informasi Perusahaan</h3>
                            <p class="mt-0.5 text-xs text-slate-500">Tampil sebagai kop di laporan PDF (Transaksi &amp; Tabung).</p>
                        </div>
                        <div class="space-y-4 p-5">
                            <div>
                                <x-input-label for="company_name" value="Nama Perusahaan / Aplikasi" class="font-semibold" />
                                <x-text-input id="company_name" name="company_name" type="text" required
                                    class="mt-1.5 block w-full rounded-xl" :value="old('company_name', $settings['company_name'] ?? '')" />
                                <x-input-error :messages="$errors->get('company_name')" class="mt-2" />
                            </div>
                            <div>
                                <x-input-label for="company_address" value="Alamat" class="font-semibold" />
                                <textarea id="company_address" name="company_address" rows="2" placeholder="cth: Jl. Industri No. 88, Bekasi"
                                    class="mt-1.5 block w-full rounded-xl border-slate-300 shadow-sm focus:border-slate-500 focus:ring-slate-500">{{ old('company_address', $settings['company_address'] ?? '') }}</textarea>
                                <x-input-error :messages="$errors->get('company_address')" class="mt-2" />
                            </div>
                            <div>
                                <x-input-label for="company_phone" value="Telepon" class="font-semibold" />
                                <x-text-input id="company_phone" name="company_phone" type="text" placeholder="cth: (021) 80001234"
                                    class="mt-1.5 block w-full rounded-xl" :value="old('company_phone', $settings['company_phone'] ?? '')" />
                                <x-input-error :messages="$errors->get('company_phone')" class="mt-2" />
                            </div>
                        </div>
                    </section>

                    {{-- B. Tampilan data --}}
                    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                        <div class="border-b border-slate-100 px-5 py-4">
                            <h3 class="text-sm font-bold text-slate-900">B. Tampilan Data</h3>
                            <p class="mt-0.5 text-xs text-slate-500">Jumlah baris per halaman pada tabel.</p>
                        </div>
                        <div class="space-y-4 p-5">
                            <div>
                                <x-input-label for="per_page" value="Baris per halaman" class="font-semibold" />
                                <select id="per_page" name="per_page" required
                                    class="mt-1.5 block w-full rounded-xl border-slate-300 shadow-sm focus:border-slate-500 focus:ring-slate-500 sm:max-w-xs">
                                    @foreach ($perPageOptions as $option)
                                        <option value="{{ $option }}" @selected((int) old('per_page', $settings['per_page'] ?? 10) === $option)>{{ $option }} baris</option>
                                    @endforeach
                                </select>
                                <x-input-error :messages="$errors->get('per_page')" class="mt-2" />
                            </div>
                            <div class="rounded-xl bg-slate-50 px-4 py-3 text-xs leading-relaxed text-slate-600">
                                Berlaku untuk tabel: Data Tabung, Jenis Gas, Supplier, Lokasi, Riwayat Transaksi, Laporan, QR Code, Users, dan Audit Log. Nilai kecil lebih ringan di HP.
                            </div>
                        </div>
                    </section>

                    {{-- C. Jenis maintenance --}}
                    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm xl:col-span-2">
                        <div class="border-b border-slate-100 px-5 py-4">
                            <h3 class="text-sm font-bold text-slate-900">C. Jenis Maintenance</h3>
                            <p class="mt-0.5 text-xs text-slate-500">Daftar pilihan pada form “Maintenance Tabung”. Satu jenis per baris.</p>
                        </div>
                        <div class="grid grid-cols-1 gap-4 p-5 lg:grid-cols-2">
                            <div>
                                <x-input-label for="maintenance_types" value="Daftar jenis (satu per baris)" class="font-semibold" />
                                <textarea id="maintenance_types" name="maintenance_types" rows="7" required
                                    class="mt-1.5 block w-full rounded-xl border-slate-300 font-mono text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500">{{ old('maintenance_types', $settings['maintenance_types'] ?? '') }}</textarea>
                                <x-input-error :messages="$errors->get('maintenance_types')" class="mt-2" />
                            </div>
                            <div class="rounded-xl bg-slate-50 p-4 text-xs leading-relaxed text-slate-600">
                                <p class="font-bold text-slate-700">Pratinjau pilihan dropdown:</p>
                                <ul class="mt-2 list-disc space-y-1 pl-5">
                                    @foreach (array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', old('maintenance_types', $settings['maintenance_types'] ?? ''))))) as $type)
                                        <li>{{ $type }}</li>
                                    @endforeach
                                </ul>
                                <p class="mt-3">Baris kosong otomatis diabaikan. Jika semua kosong, form memakai “Lainnya”.</p>
                            </div>
                        </div>
                    </section>
                </div>

                <div class="mt-5 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                    <button type="submit"
                        class="inline-flex items-center justify-center gap-2 rounded-xl bg-slate-900 px-6 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-slate-700 active:scale-[.99]">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2.2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                        Simpan Pengaturan
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
