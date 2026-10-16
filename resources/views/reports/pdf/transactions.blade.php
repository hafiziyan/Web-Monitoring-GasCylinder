<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Laporan Transaksi Gas Cylinder</title>
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
    <h1>LAPORAN TRANSAKSI GAS CYLINDER</h1>
    <p class="meta">
        Periode: {{ $filters['date_from'] ?? 'awal' }} s/d {{ $filters['date_to'] ?? 'akhir' }}
        @if (! empty($filters['type'])) | Tipe: {{ $filters['type'] }} @endif
        | Dicetak: {{ $printedAt }}
    </p>
    <table>
        <thead>
            <tr>
                <th>No</th><th>Tanggal</th><th>Kode</th><th>Gas</th><th>Tipe</th>
                <th>Asal</th><th>Tujuan</th><th>Kondisi</th><th>Sisa Gas</th><th>Operator</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($transactions as $i => $trx)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $trx->transaction_date->format('d-m-Y H:i') }}</td>
                    <td>{{ $trx->cylinder->cylinder_code }}</td>
                    <td>{{ $trx->cylinder->gasType->name }}</td>
                    <td>{{ $trx->transaction_type->value }}</td>
                    <td>{{ $trx->fromLocation?->name ?? '-' }}</td>
                    <td>{{ $trx->toLocation?->name ?? $trx->destination ?? '-' }}</td>
                    <td>{{ $trx->condition ?? '-' }}</td>
                    <td>{{ $trx->remaining_gas ?? '-' }}</td>
                    <td>{{ $trx->user->name }}</td>
                </tr>
            @empty
                <tr><td colspan="10" style="text-align:center;">Data laporan tidak ditemukan.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
