@if (session('success'))
    <div class="rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>
@endif
@if (session('info'))
    <div class="rounded-md border border-sky-200 bg-sky-50 px-4 py-3 text-sm text-sky-800">{{ session('info') }}</div>
@endif
@if (session('error'))
    <div class="rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{{ session('error') }}</div>
@endif
@if ($errors->any())
    <div class="rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
        <ul class="list-inside list-disc">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
