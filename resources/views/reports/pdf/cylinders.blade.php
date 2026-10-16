<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Laporan Data Tabung</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #111827; }
        h1 { font-size: 16px; text-align: center; margin-bottom: 2px; }
        .kop { text-align: center; font-size: 13px; font-weight: bold; margin: 0 0 2px; }
        .meta { text-align: center; color: #4b5563; margin-bottom: 12px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #9ca3af; padding: 4px 6px; text-align: left; }
        th { background: #e5e7eb; }
    </style>
</head>
<body>
    @php($company = \App\Services\Settings::company())
    <p class="kop">{{ $company['name'] }}</p>
    @if ($company['address'] || $company['phone'])
        <p class="meta" style="margin-bottom: 8px;">{{ $company['address'] }}@if ($company['address'] && $company['phone']) | @endif{{ $company['phone'] }}</p>
    @endif
    <h1>LAPORAN DATA TABUNG</h1>
    <p class="meta">
        @if (! empty($filters['status'])) Status: {{ $filters['status'] }} | @endif
        Dicetak: {{ $printedAt }}
    </p>
    <table>
        <thead>
            <tr>
                <th>No</th><th>Kode</th><th>Serial</th><th>Gas</th><th>Kapasitas</th>
                <th>Status</th><th>Kondisi</th><th>Lokasi</th><th>Supplier</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($cylinders as $i => $cylinder)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $cylinder->cylinder_code }}</td>
                    <td>{{ $cylinder->serial_number ?? '-' }}</td>
                    <td>{{ $cylinder->gasType->name }}</td>
                    <td>{{ $cylinder->capacity ?? '-' }} {{ $cylinder->unit }}</td>
                    <td>{{ $cylinder->status->value }}</td>
                    <td>{{ $cylinder->condition->value }}</td>
                    <td>{{ $cylinder->currentLocation?->name ?? '-' }}</td>
                    <td>{{ $cylinder->supplier?->name ?? '-' }}</td>
                </tr>
            @empty
                <tr><td colspan="9" style="text-align:center;">Data laporan tidak ditemukan.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
