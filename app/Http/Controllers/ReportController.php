<?php

namespace App\Http\Controllers;

use App\Enums\CylinderCondition;
use App\Enums\CylinderStatus;
use App\Enums\TransactionType;
use App\Models\Cylinder;
use App\Models\GasType;
use App\Models\Location;
use App\Models\Supplier;
use App\Models\Transaction;
use App\Services\AuditLogService;
use App\Services\Settings;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    /**
     * Query dasar laporan transaksi. Dipakai halaman web, Excel, dan PDF
     * agar ketiganya selalu menghasilkan data yang sama.
     */
    private function baseTransactionQuery(Request $request): Builder
    {
        return Transaction::with(['cylinder.gasType', 'user', 'fromLocation', 'toLocation'])
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->string('search')->toString();
                $query->where(function ($q) use ($search) {
                    $q->whereHas('cylinder', fn ($c) => $c
                        ->where('cylinder_code', 'like', "%{$search}%")
                        ->orWhere('serial_number', 'like', "%{$search}%")
                        ->orWhere('qr_token', 'like', "%{$search}%")
                        ->orWhereHas('gasType', fn ($g) => $g->where('name', 'like', "%{$search}%")))
                        ->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$search}%"));
                });
            })
            ->when($request->filled('type'), fn ($query) => $query->where('transaction_type', $request->string('type')->toString()))
            ->when($request->filled('gas_type_id'), fn ($query) => $query->whereHas('cylinder',
                fn ($c) => $c->where('gas_type_id', $request->integer('gas_type_id'))))
            ->when($request->filled('date_from'), fn ($query) => $query->where('transaction_date', '>=', $request->date('date_from')->startOfDay()))
            ->when($request->filled('date_to'), fn ($query) => $query->where('transaction_date', '<=', $request->date('date_to')->endOfDay()))
            ->when($request->filled('from_location_id'), fn ($query) => $query->where('from_location_id', $request->integer('from_location_id')))
            ->when($request->filled('to_location_id'), fn ($query) => $query->where('to_location_id', $request->integer('to_location_id')))
            ->orderByDesc('transaction_date');
    }

    /**
     * Query dasar laporan tabung. Dipakai halaman web, Excel, dan PDF.
     */
    private function baseCylinderQuery(Request $request): Builder
    {
        return Cylinder::with(['gasType', 'supplier', 'currentLocation'])
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->string('search')->toString();
                $query->where(fn ($q) => $q->where('cylinder_code', 'like', "%{$search}%")
                    ->orWhere('serial_number', 'like', "%{$search}%")
                    ->orWhere('qr_token', 'like', "%{$search}%"));
            })
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')->toString()))
            ->when($request->filled('gas_type_id'), fn ($query) => $query->where('gas_type_id', $request->integer('gas_type_id')))
            ->when($request->filled('supplier_id'), fn ($query) => $query->where('supplier_id', $request->integer('supplier_id')))
            ->when($request->filled('condition'), fn ($query) => $query->where('condition', $request->string('condition')->toString()))
            ->when($request->filled('current_location_id'), fn ($query) => $query->where('current_location_id', $request->integer('current_location_id')))
            ->orderBy('cylinder_code');
    }

    public function transactions(Request $request): View
    {
        $transactions = (clone $this->baseTransactionQuery($request))->paginate(Settings::perPage())->withQueryString();

        $typeCounts = (clone $this->baseTransactionQuery($request))
            ->selectRaw('transaction_type, COUNT(*) as total')
            ->groupBy('transaction_type')
            ->pluck('total', 'transaction_type');

        $summary = [
            'total' => array_sum($typeCounts->all()),
            'in' => (int) ($typeCounts[TransactionType::In->value] ?? 0),
            'out' => (int) ($typeCounts[TransactionType::Out->value] ?? 0),
            'return' => (int) ($typeCounts[TransactionType::Return->value] ?? 0),
            'transfer' => (int) ($typeCounts[TransactionType::Transfer->value] ?? 0),
            'maintenance' => (int) ($typeCounts[TransactionType::Maintenance->value] ?? 0),
            'use_start' => (int) ($typeCounts[TransactionType::UseStart->value] ?? 0),
            'use_end' => (int) ($typeCounts[TransactionType::UseEnd->value] ?? 0),
        ];

        return view('reports.transactions', [
            'transactions' => $transactions,
            'summary' => $summary,
            'types' => TransactionType::cases(),
            'gasTypes' => GasType::orderBy('name')->get(),
            'locations' => Location::orderBy('name')->get(),
            'filters' => $request->only(['search', 'type', 'gas_type_id', 'date_from', 'date_to', 'from_location_id', 'to_location_id']),
        ]);
    }

    public function cylinders(Request $request): View
    {
        $cylinders = (clone $this->baseCylinderQuery($request))->paginate(Settings::perPage())->withQueryString();

        $statusCounts = (clone $this->baseCylinderQuery($request))
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $summary = [
            'total' => array_sum($statusCounts->all()),
            'available' => (int) ($statusCounts[CylinderStatus::Available->value] ?? 0),
            'in_use' => (int) ($statusCounts[CylinderStatus::InUse->value] ?? 0),
            'empty' => (int) ($statusCounts[CylinderStatus::Empty->value] ?? 0),
            'maintenance' => (int) ($statusCounts[CylinderStatus::Maintenance->value] ?? 0),
            'outside' => (int) ($statusCounts[CylinderStatus::Outside->value] ?? 0),
            'inactive' => (int) ($statusCounts[CylinderStatus::Inactive->value] ?? 0),
        ];

        return view('reports.cylinders', [
            'cylinders' => $cylinders,
            'summary' => $summary,
            'statuses' => CylinderStatus::cases(),
            'conditions' => CylinderCondition::cases(),
            'gasTypes' => GasType::orderBy('name')->get(),
            'suppliers' => Supplier::orderBy('name')->get(),
            'locations' => Location::orderBy('name')->get(),
            'filters' => $request->only(['search', 'status', 'gas_type_id', 'supplier_id', 'condition', 'current_location_id']),
        ]);
    }

    public function transactionsExcel(Request $request): StreamedResponse
    {
        $transactions = $this->baseTransactionQuery($request)->get();

        AuditLogService::log(
            $request->user(),
            'EXPORT_EXCEL',
            'REPORT',
            'Export laporan transaksi ke Excel.',
            null,
            [],
            $request->only(['search', 'type', 'gas_type_id', 'date_from', 'date_to', 'from_location_id', 'to_location_id']),
        );

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray([
            ['No', 'Tanggal', 'Kode Tabung', 'Serial Number', 'Jenis Gas', 'Tipe Transaksi',
                'Lokasi Asal', 'Lokasi Tujuan', 'Tujuan', 'Kondisi', 'Sisa Gas', 'Operator', 'Catatan'],
        ]);

        $row = 2;
        foreach ($transactions as $i => $trx) {
            $sheet->fromArray([[
                $i + 1,
                $trx->transaction_date->format('Y-m-d H:i'),
                $trx->cylinder->cylinder_code,
                $trx->cylinder->serial_number,
                $trx->cylinder->gasType->name,
                $trx->transaction_type->value,
                $trx->fromLocation?->name,
                $trx->toLocation?->name,
                $trx->destination,
                $trx->condition,
                $trx->remaining_gas !== null ? (float) $trx->remaining_gas : null,
                $trx->user->name,
                $trx->notes,
            ]], null, "A{$row}");
            $row++;
        }

        foreach (range('A', 'M') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        $filename = 'laporan-transaksi-'.now()->format('Y-m-d').'.xlsx';

        return response()->streamDownload(function () use ($spreadsheet) {
            (new Xlsx($spreadsheet))->save('php://output');
        }, $filename, ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
    }

    public function cylindersExcel(Request $request): StreamedResponse
    {
        $cylinders = $this->baseCylinderQuery($request)->get();

        AuditLogService::log(
            $request->user(),
            'EXPORT_EXCEL',
            'REPORT',
            'Export laporan tabung ke Excel.',
            null,
            [],
            $request->only(['search', 'status', 'gas_type_id', 'supplier_id', 'condition', 'current_location_id']),
        );

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray([
            ['No', 'Kode Tabung', 'Serial Number', 'Jenis Gas', 'Kapasitas', 'Supplier',
                'Status', 'Kondisi', 'Lokasi Saat Ini', 'QR Token', 'Update Terakhir'],
        ]);

        $row = 2;
        foreach ($cylinders as $i => $cylinder) {
            $sheet->fromArray([[
                $i + 1,
                $cylinder->cylinder_code,
                $cylinder->serial_number,
                $cylinder->gasType->name,
                $cylinder->capacity !== null ? (float) $cylinder->capacity.' '.$cylinder->unit : null,
                $cylinder->supplier?->name,
                $cylinder->status->value,
                $cylinder->condition->value,
                $cylinder->currentLocation?->name,
                $cylinder->qr_token,
                $cylinder->updated_at->format('Y-m-d H:i'),
            ]], null, "A{$row}");
            $row++;
        }

        foreach (range('A', 'K') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        $filename = 'laporan-tabung-'.now()->format('Y-m-d').'.xlsx';

        return response()->streamDownload(function () use ($spreadsheet) {
            (new Xlsx($spreadsheet))->save('php://output');
        }, $filename, ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
    }

    public function transactionsPdf(Request $request)
    {
        $transactions = $this->baseTransactionQuery($request)->get();

        AuditLogService::log(
            $request->user(),
            'EXPORT_PDF',
            'REPORT',
            'Export laporan transaksi ke PDF.',
            null,
            [],
            $request->only(['search', 'type', 'gas_type_id', 'date_from', 'date_to', 'from_location_id', 'to_location_id']),
        );

        $html = view('reports.pdf.transactions', [
            'transactions' => $transactions,
            'filters' => $request->only(['search', 'type', 'gas_type_id', 'date_from', 'date_to', 'from_location_id', 'to_location_id']),
            'printedAt' => now()->format('d M Y H:i'),
        ])->render();

        return $this->pdfResponse($html, 'laporan-transaksi-'.now()->format('Y-m-d').'.pdf');
    }

    public function cylindersPdf(Request $request)
    {
        $cylinders = $this->baseCylinderQuery($request)->get();

        AuditLogService::log(
            $request->user(),
            'EXPORT_PDF',
            'REPORT',
            'Export laporan tabung ke PDF.',
            null,
            [],
            $request->only(['search', 'status', 'gas_type_id', 'supplier_id', 'condition', 'current_location_id']),
        );

        $html = view('reports.pdf.cylinders', [
            'cylinders' => $cylinders,
            'filters' => $request->only(['search', 'status', 'gas_type_id', 'supplier_id', 'condition', 'current_location_id']),
            'printedAt' => now()->format('d M Y H:i'),
        ])->render();

        return $this->pdfResponse($html, 'laporan-tabung-'.now()->format('Y-m-d').'.pdf');
    }

    private function pdfResponse(string $html, string $filename)
    {
        $options = new Options;
        $options->set('isRemoteEnabled', false);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();

        return response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }
}
