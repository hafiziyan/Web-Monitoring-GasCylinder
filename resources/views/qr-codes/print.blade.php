<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Cetak QR Code</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: ui-sans-serif, system-ui, sans-serif; background: #f3f4f6; color: #111827; }
        .toolbar { display: flex; gap: 12px; align-items: center; padding: 16px 24px; background: #fff; border-bottom: 1px solid #e5e7eb; }
        .toolbar a, .toolbar button { font-size: 14px; font-weight: 500; border-radius: 6px; padding: 8px 16px; cursor: pointer; text-decoration: none; }
        .btn-print { background: #1e293b; color: #fff; border: none; }
        .btn-back { border: 1px solid #d1d5db; color: #374151; background: #fff; }
        .labels { display: flex; flex-wrap: wrap; gap: 24px; justify-content: center; padding: 32px 24px; }
        .label { width: 280px; border: 2px solid #111827; border-radius: 8px; background: #fff; padding: 16px 12px; text-align: center; page-break-inside: avoid; }
        .label .brand { font-size: 13px; font-weight: 700; letter-spacing: 2px; }
        .label .qr { margin: 12px auto; width: 180px; }
        .label .qr svg { width: 100%; height: auto; display: block; }
        .label .code { font-size: 18px; font-weight: 800; letter-spacing: 1px; }
        .label .gas { font-size: 13px; font-weight: 600; text-transform: uppercase; color: #374151; }
        .label .serial { font-size: 12px; color: #6b7280; }
        @media print {
            body { background: #fff; }
            .toolbar { display: none; }
            .labels { padding: 0; gap: 16px; }
            .label { border-width: 1.5px; }
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <button type="button" class="btn-print" onclick="window.print()">Print</button>
        <a href="{{ route('qr-codes.index') }}" class="btn-back">Kembali</a>
    </div>
    <div class="labels">
        @foreach ($cylinders as $cylinder)
            <div class="label">
                <p class="brand">GAS CYLINDER</p>
                <div class="qr">{!! $qrImages[$cylinder->id] !!}</div>
                <p class="code">{{ $cylinder->cylinder_code }}</p>
                <p class="gas">{{ $cylinder->gasType->name }}</p>
                <p class="serial">Serial: {{ $cylinder->serial_number ?? '-' }}</p>
            </div>
        @endforeach
    </div>
</body>
</html>
