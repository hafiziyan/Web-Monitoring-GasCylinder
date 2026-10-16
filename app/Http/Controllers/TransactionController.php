<?php

namespace App\Http\Controllers;

use App\Enums\CylinderCondition;
use App\Enums\CylinderStatus;
use App\Enums\TransactionType;
use App\Http\Requests\Transaction\StoreInRequest;
use App\Http\Requests\Transaction\StoreMaintenanceCompleteRequest;
use App\Http\Requests\Transaction\StoreMaintenanceRequest;
use App\Http\Requests\Transaction\StoreOutRequest;
use App\Http\Requests\Transaction\StoreReturnRequest;
use App\Http\Requests\Transaction\StoreTransferRequest;
use App\Http\Requests\Transaction\StoreUseEndRequest;
use App\Http\Requests\Transaction\StoreUseStartRequest;
use App\Models\Cylinder;
use App\Models\GasType;
use App\Models\Location;
use App\Models\Transaction;
use App\Services\AuditLogService;
use App\Services\Settings;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class TransactionController extends Controller
{
    public function index(Request $request): View
    {
        $transactions = Transaction::with(['cylinder.gasType', 'user', 'fromLocation', 'toLocation'])
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
            ->orderByDesc('transaction_date')
            ->paginate(Settings::perPage())
            ->withQueryString();

        return view('transactions.index', [
            'transactions' => $transactions,
            'types' => TransactionType::cases(),
            'gasTypes' => GasType::orderBy('name')->get(),
            'locations' => Location::orderBy('name')->get(),
            'filters' => $request->only(['search', 'type', 'gas_type_id', 'date_from', 'date_to', 'from_location_id', 'to_location_id']),
        ]);
    }

    public function show(Transaction $transaction): View
    {
        $transaction->load(['cylinder.gasType', 'cylinder.supplier', 'cylinder.currentLocation', 'user', 'fromLocation', 'toLocation']);

        return view('transactions.show', compact('transaction'));
    }

    public function scan(Request $request): View|RedirectResponse
    {
        // Fallback non-JS: form manual GET ?token=XXX.
        if ($request->filled('token')) {
            return redirect()->route('transactions.scan-result', [
                'qr_token' => $request->string('token')->toString(),
            ]);
        }

        return view('transactions.scan');
    }

    /**
     * Petakan cylinder ke array detail untuk komponen selector.
     */
    private function cylinderDetail(?Cylinder $cylinder): ?array
    {
        if (! $cylinder) {
            return null;
        }

        return [
            'id' => $cylinder->id,
            'cylinder_code' => $cylinder->cylinder_code,
            'serial_number' => $cylinder->serial_number,
            'qr_token' => $cylinder->qr_token,
            'gas_type' => $cylinder->gasType->name,
            'status' => $cylinder->status->value,
            'condition' => $cylinder->condition->value,
            'capacity' => $cylinder->capacity,
            'unit' => $cylinder->unit,
            'supplier' => $cylinder->supplier?->name,
            'current_location' => $cylinder->currentLocation?->name,
            'last_destination' => $cylinder->transactions()
                ->where('transaction_type', TransactionType::Out)
                ->orderByDesc('transaction_date')
                ->value('destination'),
        ];
    }

    /**
     * Pencarian tabung untuk komponen selector (AJAX).
     * Maksimal 10 hasil, hanya field yang dibutuhkan UI.
     */
    public function searchCylinders(Request $request): JsonResponse
    {
        $query = trim($request->string('q')->toString());

        $statuses = collect(explode(',', $request->string('status')->toString()))
            ->map(fn ($s) => trim($s))
            ->filter(fn ($s) => in_array($s, array_column(CylinderStatus::cases(), 'value'), true))
            ->values();

        $cylinders = Cylinder::with(['gasType', 'supplier', 'currentLocation'])
            ->when($query !== '', fn ($q) => $q->where(fn ($w) => $w
                ->where('cylinder_code', 'like', "%{$query}%")
                ->orWhere('serial_number', 'like', "%{$query}%")
                ->orWhere('qr_token', 'like', "%{$query}%")))
            ->when($statuses->isNotEmpty(), fn ($q) => $q->whereIn('status', $statuses->all()))
            ->orderBy('cylinder_code')
            ->limit(10)
            ->get();

        $lastDestinations = Transaction::whereIn('cylinder_id', $cylinders->pluck('id'))
            ->where('transaction_type', TransactionType::Out)
            ->orderByDesc('transaction_date')
            ->get()
            ->groupBy('cylinder_id')
            ->map(fn ($rows) => $rows->first()->destination);

        return response()->json($cylinders->map(fn (Cylinder $cylinder) => [
            'id' => $cylinder->id,
            'cylinder_code' => $cylinder->cylinder_code,
            'serial_number' => $cylinder->serial_number,
            'qr_token' => $cylinder->qr_token,
            'gas_type' => $cylinder->gasType->name,
            'status' => $cylinder->status->value,
            'condition' => $cylinder->condition->value,
            'capacity' => $cylinder->capacity,
            'unit' => $cylinder->unit,
            'supplier' => $cylinder->supplier?->name,
            'current_location' => $cylinder->currentLocation?->name,
            'last_destination' => $lastDestinations->get($cylinder->id),
        ])->values());
    }

    /**
     * Identifikasi tabung murni read-only: tidak mengubah status/lokasi/token.
     */
    public function scanResult(string $qr_token): View
    {
        $cylinder = Cylinder::with(['gasType', 'supplier', 'currentLocation'])
            ->where('qr_token', $qr_token)
            ->first();

        return view('transactions.scan-result', compact('cylinder'));
    }

    public function createIn(Request $request): View
    {
        $selectedCylinder = null;

        if ($request->filled('cylinder')) {
            $keyword = $request->string('cylinder')->toString();
            $selectedCylinder = Cylinder::with(['gasType', 'supplier', 'currentLocation'])
                ->where('cylinder_code', $keyword)
                ->orWhere('qr_token', $keyword)
                ->first();
        }

        // Fallback: setelah validasi gagal / penolakan via back(),
        // pertahankan tabung dari input lama.
        if (! $selectedCylinder && old('cylinder_id')) {
            $selectedCylinder = Cylinder::with(['gasType', 'supplier', 'currentLocation'])
                ->find(old('cylinder_id'));
        }

        return view('transactions.in', [
            'selectedCylinder' => $selectedCylinder,
            'selectedDetail' => $this->cylinderDetail($selectedCylinder),
            'searchKeyword' => $request->string('cylinder')->toString(),
            'locations' => Location::where('is_active', true)->orderBy('name')->get(),
            'conditions' => CylinderCondition::cases(),
        ]);
    }

    /**
     * Catat audit transaksi. Dipanggil di dalam DB::transaction pemanggil
     * agar ikut rollback jika transaksi gagal.
     */
    private function auditTransaction(
        Request $request,
        Transaction $transaction,
        Cylinder $cylinder,
        string $action,
        string $description,
        string $statusAfter,
    ): void {
        AuditLogService::log(
            $request->user(),
            $action,
            'TRANSACTION',
            $description,
            $transaction,
            [],
            [
                'transaction_type' => $transaction->transaction_type->value,
                'cylinder_id' => $transaction->cylinder_id,
                'from_location_id' => $transaction->from_location_id,
                'to_location_id' => $transaction->to_location_id,
                'condition' => $transaction->condition,
                'remaining_gas' => $transaction->remaining_gas,
                'status_after' => $statusAfter,
            ],
        );
    }

    public function storeIn(StoreInRequest $request): RedirectResponse
    {
        // Kunci baris tabung dulu agar dua request bersamaan tidak lolos
        // pengecekan status secara bersamaan (TOCTOU race condition).
        $outcome = DB::transaction(function () use ($request) {
            $cylinder = Cylinder::whereKey($request->integer('cylinder_id'))->lockForUpdate()->firstOrFail();

            // IN hanya untuk tabung internal (AVAILABLE/EMPTY). OUTSIDE wajib
            // via RETURN, IN_USE tidak masuk akal, MAINTENANCE via
            // maintenance-complete, INACTIVE tidak bisa masuk.
            if (! in_array($cylinder->status, [CylinderStatus::Available, CylinderStatus::Empty], true)) {
                return ['rejected' => true, 'cylinder' => $cylinder];
            }

            $transaction = Transaction::create([
                'cylinder_id' => $cylinder->id,
                'user_id' => $request->user()->id,
                'transaction_type' => TransactionType::In,
                'from_location_id' => null,
                'to_location_id' => $request->integer('to_location_id'),
                'condition' => $request->string('condition')->toString(),
                'remaining_gas' => $request->filled('remaining_gas') ? $request->input('remaining_gas') : null,
                'destination' => null,
                'notes' => $request->input('notes'),
                'transaction_date' => $request->date('transaction_date'),
            ]);

            $cylinder->update([
                'status' => CylinderStatus::Available,
                'current_location_id' => $request->integer('to_location_id'),
                'condition' => $request->string('condition')->toString(),
            ]);

            $toName = Location::whereKey($request->integer('to_location_id'))->value('name');
            $this->auditTransaction(
                $request,
                $transaction,
                $cylinder,
                'IN',
                "Tabung {$cylinder->cylinder_code} masuk ke {$toName}.",
                CylinderStatus::Available->value,
            );

            return ['rejected' => false, 'cylinder' => $cylinder];
        });

        if ($outcome['rejected']) {
            return back()
                ->withInput()
                ->with('error', "Transaksi IN hanya untuk tabung AVAILABLE/EMPTY (status saat ini: {$outcome['cylinder']->status->value}).");
        }

        return redirect()
            ->route('cylinders.show', $outcome['cylinder'])
            ->with('success', 'Transaksi Tabung Masuk berhasil disimpan.');
    }

    public function createOut(Request $request): View
    {
        $selectedCylinder = null;

        if ($request->filled('cylinder')) {
            $keyword = $request->string('cylinder')->toString();
            $selectedCylinder = Cylinder::with(['gasType', 'supplier', 'currentLocation'])
                ->where('cylinder_code', $keyword)
                ->orWhere('qr_token', $keyword)
                ->first();
        }

        // Fallback: setelah validasi gagal / penolakan via back(),
        // pertahankan tabung dari input lama.
        if (! $selectedCylinder && old('cylinder_id')) {
            $selectedCylinder = Cylinder::with(['gasType', 'supplier', 'currentLocation'])
                ->find(old('cylinder_id'));
        }

        return view('transactions.out', [
            'selectedCylinder' => $selectedCylinder,
            'selectedDetail' => $this->cylinderDetail($selectedCylinder),
            'searchKeyword' => $request->string('cylinder')->toString(),
            'conditions' => CylinderCondition::cases(),
        ]);
    }

    public function storeOut(StoreOutRequest $request): RedirectResponse
    {
        $outcome = DB::transaction(function () use ($request) {
            $cylinder = Cylinder::whereKey($request->integer('cylinder_id'))->lockForUpdate()->firstOrFail();

            if (! in_array($cylinder->status, [CylinderStatus::Available, CylinderStatus::Empty], true)) {
                return ['rejected' => true, 'cylinder' => $cylinder];
            }

            $fromName = Location::whereKey($cylinder->current_location_id)->value('name');
            $destination = $request->string('destination')->toString();

            $transaction = Transaction::create([
                'cylinder_id' => $cylinder->id,
                'user_id' => $request->user()->id,
                'transaction_type' => TransactionType::Out,
                'from_location_id' => $cylinder->current_location_id,
                'to_location_id' => null,
                'condition' => $request->string('condition')->toString(),
                'remaining_gas' => $request->filled('remaining_gas') ? $request->input('remaining_gas') : null,
                'destination' => $destination,
                'notes' => $request->input('notes'),
                'transaction_date' => $request->date('transaction_date'),
            ]);

            $cylinder->update([
                'status' => CylinderStatus::Outside,
                'current_location_id' => null,
                'condition' => $request->string('condition')->toString(),
            ]);

            $this->auditTransaction(
                $request,
                $transaction,
                $cylinder,
                'OUT',
                "Tabung {$cylinder->cylinder_code} keluar dari {$fromName} ke {$destination}.",
                CylinderStatus::Outside->value,
            );

            return ['rejected' => false, 'cylinder' => $cylinder];
        });

        if ($outcome['rejected']) {
            return back()
                ->withInput()
                ->with('error', "Tabung tidak dapat dikeluarkan karena status saat ini adalah {$outcome['cylinder']->status->value}.");
        }

        return redirect()
            ->route('cylinders.show', $outcome['cylinder'])
            ->with('success', 'Transaksi Tabung Keluar berhasil disimpan.');
    }

    public function createReturn(Request $request): View
    {
        $selectedCylinder = null;
        $lastDestination = null;

        if ($request->filled('cylinder')) {
            $keyword = $request->string('cylinder')->toString();
            $selectedCylinder = Cylinder::with(['gasType', 'supplier', 'currentLocation'])
                ->where('cylinder_code', $keyword)
                ->orWhere('qr_token', $keyword)
                ->first();

            $lastDestination = $selectedCylinder?->transactions()
                ->where('transaction_type', TransactionType::Out)
                ->orderByDesc('transaction_date')
                ->value('destination');
        }

        // Fallback: setelah validasi gagal / penolakan via back(),
        // pertahankan tabung dari input lama.
        if (! $selectedCylinder && old('cylinder_id')) {
            $selectedCylinder = Cylinder::with(['gasType', 'supplier', 'currentLocation'])
                ->find(old('cylinder_id'));

            $lastDestination ??= $selectedCylinder?->transactions()
                ->where('transaction_type', TransactionType::Out)
                ->orderByDesc('transaction_date')
                ->value('destination');
        }

        return view('transactions.return', [
            'selectedCylinder' => $selectedCylinder,
            'selectedDetail' => $this->cylinderDetail($selectedCylinder),
            'searchKeyword' => $request->string('cylinder')->toString(),
            'lastDestination' => $lastDestination,
            'locations' => Location::where('is_active', true)->orderBy('name')->get(),
            'conditions' => CylinderCondition::cases(),
        ]);
    }

    public function storeReturn(StoreReturnRequest $request): RedirectResponse
    {
        $outcome = DB::transaction(function () use ($request) {
            $cylinder = Cylinder::whereKey($request->integer('cylinder_id'))->lockForUpdate()->firstOrFail();

            if ($cylinder->status !== CylinderStatus::Outside) {
                return ['rejected' => true, 'cylinder' => $cylinder];
            }

            $transaction = Transaction::create([
                'cylinder_id' => $cylinder->id,
                'user_id' => $request->user()->id,
                'transaction_type' => TransactionType::Return,
                'from_location_id' => null,
                'to_location_id' => $request->integer('to_location_id'),
                'condition' => $request->string('condition')->toString(),
                'remaining_gas' => $request->filled('remaining_gas') ? $request->input('remaining_gas') : null,
                'destination' => null,
                'notes' => $request->input('notes'),
                'transaction_date' => $request->date('transaction_date'),
            ]);

            $cylinder->update([
                'status' => CylinderStatus::Available,
                'current_location_id' => $request->integer('to_location_id'),
                'condition' => $request->string('condition')->toString(),
            ]);

            $toName = Location::whereKey($request->integer('to_location_id'))->value('name');
            $this->auditTransaction(
                $request,
                $transaction,
                $cylinder,
                'RETURN',
                "Tabung {$cylinder->cylinder_code} kembali (return) ke {$toName}.",
                CylinderStatus::Available->value,
            );

            return ['rejected' => false, 'cylinder' => $cylinder];
        });

        if ($outcome['rejected']) {
            return back()
                ->withInput()
                ->with('error', "Tabung tidak dapat di-return karena status saat ini adalah {$outcome['cylinder']->status->value}.");
        }

        return redirect()
            ->route('cylinders.show', $outcome['cylinder'])
            ->with('success', 'Tabung berhasil di-return.');
    }

    public function createTransfer(Request $request): View
    {
        $selectedCylinder = null;

        if ($request->filled('cylinder')) {
            $keyword = $request->string('cylinder')->toString();
            $selectedCylinder = Cylinder::with(['gasType', 'supplier', 'currentLocation'])
                ->where('cylinder_code', $keyword)
                ->orWhere('qr_token', $keyword)
                ->first();
        }

        // Fallback: setelah validasi gagal / penolakan via back(),
        // pertahankan tabung dari input lama.
        if (! $selectedCylinder && old('cylinder_id')) {
            $selectedCylinder = Cylinder::with(['gasType', 'supplier', 'currentLocation'])
                ->find(old('cylinder_id'));
        }

        return view('transactions.transfer', [
            'selectedCylinder' => $selectedCylinder,
            'selectedDetail' => $this->cylinderDetail($selectedCylinder),
            'searchKeyword' => $request->string('cylinder')->toString(),
            'locations' => Location::where('is_active', true)->orderBy('name')->get(),
            'conditions' => CylinderCondition::cases(),
        ]);
    }

    public function storeTransfer(StoreTransferRequest $request): RedirectResponse
    {
        $outcome = DB::transaction(function () use ($request) {
            $cylinder = Cylinder::whereKey($request->integer('cylinder_id'))->lockForUpdate()->firstOrFail();

            if (! in_array($cylinder->status, [CylinderStatus::Available, CylinderStatus::Empty], true)) {
                return ['rejected' => true, 'reason' => 'status', 'cylinder' => $cylinder];
            }

            if ((int) $cylinder->current_location_id === $request->integer('to_location_id')) {
                return ['rejected' => true, 'reason' => 'same_location', 'cylinder' => $cylinder];
            }

            $fromName = Location::whereKey($cylinder->current_location_id)->value('name');

            $transaction = Transaction::create([
                'cylinder_id' => $cylinder->id,
                'user_id' => $request->user()->id,
                'transaction_type' => TransactionType::Transfer,
                'from_location_id' => $cylinder->current_location_id,
                'to_location_id' => $request->integer('to_location_id'),
                'condition' => $request->string('condition')->toString(),
                'remaining_gas' => $request->filled('remaining_gas') ? $request->input('remaining_gas') : null,
                'destination' => null,
                'notes' => $request->input('notes'),
                'transaction_date' => $request->date('transaction_date'),
            ]);

            $cylinder->update([
                'current_location_id' => $request->integer('to_location_id'),
                'condition' => $request->string('condition')->toString(),
            ]);

            $toName = Location::whereKey($request->integer('to_location_id'))->value('name');
            $this->auditTransaction(
                $request,
                $transaction,
                $cylinder,
                'TRANSFER',
                "Tabung {$cylinder->cylinder_code} dipindahkan dari {$fromName} ke {$toName}.",
                $cylinder->status->value,
            );

            return ['rejected' => false, 'cylinder' => $cylinder];
        });

        if ($outcome['rejected']) {
            $message = $outcome['reason'] === 'same_location'
                ? 'Lokasi tujuan sama dengan lokasi saat ini.'
                : "Tabung tidak dapat dipindahkan karena status saat ini adalah {$outcome['cylinder']->status->value}.";

            return back()->withInput()->with('error', $message);
        }

        return redirect()
            ->route('cylinders.show', $outcome['cylinder'])
            ->with('success', 'Transaksi Transfer berhasil disimpan.');
    }

    public function createUseStart(Request $request): View
    {
        $selectedCylinder = null;

        if ($request->filled('cylinder')) {
            $keyword = $request->string('cylinder')->toString();
            $selectedCylinder = Cylinder::with(['gasType', 'supplier', 'currentLocation'])
                ->where('cylinder_code', $keyword)
                ->orWhere('qr_token', $keyword)
                ->first();
        }

        // Fallback: setelah validasi gagal / penolakan via back(),
        // pertahankan tabung dari input lama.
        if (! $selectedCylinder && old('cylinder_id')) {
            $selectedCylinder = Cylinder::with(['gasType', 'supplier', 'currentLocation'])
                ->find(old('cylinder_id'));
        }

        return view('transactions.use-start', [
            'selectedCylinder' => $selectedCylinder,
            'selectedDetail' => $this->cylinderDetail($selectedCylinder),
            'searchKeyword' => $request->string('cylinder')->toString(),
            'conditions' => CylinderCondition::cases(),
        ]);
    }

    public function storeUseStart(StoreUseStartRequest $request): RedirectResponse
    {
        $outcome = DB::transaction(function () use ($request) {
            $cylinder = Cylinder::whereKey($request->integer('cylinder_id'))->lockForUpdate()->firstOrFail();

            if ($cylinder->status !== CylinderStatus::Available) {
                return ['rejected' => true, 'cylinder' => $cylinder];
            }

            $transaction = Transaction::create([
                'cylinder_id' => $cylinder->id,
                'user_id' => $request->user()->id,
                'transaction_type' => TransactionType::UseStart,
                'from_location_id' => $cylinder->current_location_id,
                'to_location_id' => $cylinder->current_location_id,
                'condition' => $request->string('condition')->toString(),
                'remaining_gas' => $request->filled('remaining_gas') ? $request->input('remaining_gas') : null,
                'destination' => null,
                'notes' => $request->input('notes'),
                'transaction_date' => $request->date('transaction_date'),
            ]);

            $cylinder->update([
                'status' => CylinderStatus::InUse,
                'condition' => $request->string('condition')->toString(),
            ]);

            $this->auditTransaction(
                $request,
                $transaction,
                $cylinder,
                'USE_START',
                "Tabung {$cylinder->cylinder_code} mulai dipakai produksi.",
                CylinderStatus::InUse->value,
            );

            return ['rejected' => false, 'cylinder' => $cylinder];
        });

        if ($outcome['rejected']) {
            return back()
                ->withInput()
                ->with('error', "Tabung tidak dapat mulai dipakai karena status saat ini adalah {$outcome['cylinder']->status->value}.");
        }

        return redirect()
            ->route('cylinders.show', $outcome['cylinder'])
            ->with('success', 'Tabung mulai dipakai produksi.');
    }

    public function createUseEnd(Request $request): View
    {
        $selectedCylinder = null;

        if ($request->filled('cylinder')) {
            $keyword = $request->string('cylinder')->toString();
            $selectedCylinder = Cylinder::with(['gasType', 'supplier', 'currentLocation'])
                ->where('cylinder_code', $keyword)
                ->orWhere('qr_token', $keyword)
                ->first();
        }

        // Fallback: setelah validasi gagal / penolakan via back(),
        // pertahankan tabung dari input lama.
        if (! $selectedCylinder && old('cylinder_id')) {
            $selectedCylinder = Cylinder::with(['gasType', 'supplier', 'currentLocation'])
                ->find(old('cylinder_id'));
        }

        return view('transactions.use-end', [
            'selectedCylinder' => $selectedCylinder,
            'selectedDetail' => $this->cylinderDetail($selectedCylinder),
            'searchKeyword' => $request->string('cylinder')->toString(),
            'conditions' => CylinderCondition::cases(),
        ]);
    }

    public function storeUseEnd(StoreUseEndRequest $request): RedirectResponse
    {
        $outcome = DB::transaction(function () use ($request) {
            $cylinder = Cylinder::whereKey($request->integer('cylinder_id'))->lockForUpdate()->firstOrFail();

            if ($cylinder->status !== CylinderStatus::InUse) {
                return ['rejected' => true, 'cylinder' => $cylinder];
            }

            // Selesai pakai dengan sisa gas > 0 → kembali AVAILABLE (masih
            // bisa dipakai); tanpa sisa → EMPTY, siap keluar isi ulang.
            $leftover = $request->filled('remaining_gas') ? (float) $request->input('remaining_gas') : 0.0;
            $endStatus = $leftover > 0 ? CylinderStatus::Available : CylinderStatus::Empty;

            $transaction = Transaction::create([
                'cylinder_id' => $cylinder->id,
                'user_id' => $request->user()->id,
                'transaction_type' => TransactionType::UseEnd,
                'from_location_id' => $cylinder->current_location_id,
                'to_location_id' => $cylinder->current_location_id,
                'condition' => $request->string('condition')->toString(),
                'remaining_gas' => $request->filled('remaining_gas') ? $request->input('remaining_gas') : null,
                'destination' => null,
                'notes' => $request->input('notes'),
                'transaction_date' => $request->date('transaction_date'),
            ]);

            $cylinder->update([
                'status' => $endStatus,
                'condition' => $request->string('condition')->toString(),
            ]);

            $this->auditTransaction(
                $request,
                $transaction,
                $cylinder,
                'USE_END',
                "Pemakaian tabung {$cylinder->cylinder_code} selesai (status: {$endStatus->value}).",
                $endStatus->value,
            );

            return ['rejected' => false, 'cylinder' => $cylinder];
        });

        if ($outcome['rejected']) {
            return back()
                ->withInput()
                ->with('error', "Tabung tidak sedang dipakai (status saat ini: {$outcome['cylinder']->status->value}).");
        }

        $doneStatus = $outcome['cylinder']->status->value;

        return redirect()
            ->route('cylinders.show', $outcome['cylinder'])
            ->with('success', "Pemakaian tabung selesai. Status menjadi {$doneStatus}.");
    }

    public function createMaintenance(Request $request): View
    {
        $selectedCylinder = null;

        if ($request->filled('cylinder')) {
            $keyword = $request->string('cylinder')->toString();
            $selectedCylinder = Cylinder::with(['gasType', 'supplier', 'currentLocation'])
                ->where('cylinder_code', $keyword)
                ->orWhere('qr_token', $keyword)
                ->first();
        }

        // Fallback: setelah validasi gagal, pertahankan tabung dari input lama.
        if (! $selectedCylinder && old('cylinder_id')) {
            $selectedCylinder = Cylinder::with(['gasType', 'supplier', 'currentLocation'])
                ->find(old('cylinder_id'));
        }

        return view('transactions.maintenance', [
            'selectedCylinder' => $selectedCylinder,
            'selectedDetail' => $this->cylinderDetail($selectedCylinder),
            'searchKeyword' => $request->string('cylinder')->toString(),
            'locations' => Location::where('is_active', true)->orderBy('name')->get(),
            'conditions' => CylinderCondition::cases(),
            'maintenanceTypes' => Settings::maintenanceTypes(),
        ]);
    }

    public function storeMaintenance(StoreMaintenanceRequest $request): RedirectResponse
    {
        $outcome = DB::transaction(function () use ($request) {
            $cylinder = Cylinder::whereKey($request->integer('cylinder_id'))->lockForUpdate()->firstOrFail();

            if (! in_array($cylinder->status, [CylinderStatus::Available, CylinderStatus::Empty], true)) {
                return ['rejected' => true, 'cylinder' => $cylinder];
            }

            $maintenanceType = $request->string('maintenance_type')->toString();
            $notes = $request->filled('notes')
                ? "Maintenance: {$maintenanceType} | Catatan: {$request->string('notes')->toString()}"
                : "Maintenance: {$maintenanceType}";

            $transaction = Transaction::create([
                'cylinder_id' => $cylinder->id,
                'user_id' => $request->user()->id,
                'transaction_type' => TransactionType::Maintenance,
                'from_location_id' => $cylinder->current_location_id,
                'to_location_id' => $request->integer('to_location_id'),
                'condition' => $request->string('condition')->toString(),
                'remaining_gas' => $request->filled('remaining_gas') ? $request->input('remaining_gas') : null,
                'destination' => null,
                'notes' => $notes,
                'transaction_date' => $request->date('transaction_date'),
            ]);

            $cylinder->update([
                'status' => CylinderStatus::Maintenance,
                'current_location_id' => $request->integer('to_location_id'),
                'condition' => $request->string('condition')->toString(),
            ]);

            $toName = Location::whereKey($request->integer('to_location_id'))->value('name');
            $this->auditTransaction(
                $request,
                $transaction,
                $cylinder,
                'MAINTENANCE',
                "Tabung {$cylinder->cylinder_code} masuk maintenance di {$toName}.",
                CylinderStatus::Maintenance->value,
            );

            return ['rejected' => false, 'cylinder' => $cylinder];
        });

        if ($outcome['rejected']) {
            return back()
                ->withInput()
                ->with('error', "Tabung tidak dapat masuk maintenance karena status saat ini adalah {$outcome['cylinder']->status->value}.");
        }

        return redirect()
            ->route('cylinders.show', $outcome['cylinder'])
            ->with('success', 'Tabung berhasil masuk maintenance.');
    }

    public function createMaintenanceComplete(Request $request): View
    {
        $selectedCylinder = null;
        $lastRemainingGas = null;

        if ($request->filled('cylinder')) {
            $keyword = $request->string('cylinder')->toString();
            $selectedCylinder = Cylinder::with(['gasType', 'supplier', 'currentLocation'])
                ->where('cylinder_code', $keyword)
                ->orWhere('qr_token', $keyword)
                ->first();

            $lastRemainingGas = $selectedCylinder?->transactions()
                ->orderByDesc('transaction_date')
                ->value('remaining_gas');
        }

        // Fallback: setelah validasi gagal, pertahankan tabung dari input lama.
        if (! $selectedCylinder && old('cylinder_id')) {
            $selectedCylinder = Cylinder::with(['gasType', 'supplier', 'currentLocation'])
                ->find(old('cylinder_id'));

            $lastRemainingGas ??= $selectedCylinder?->transactions()
                ->orderByDesc('transaction_date')
                ->value('remaining_gas');
        }

        return view('transactions.maintenance-complete', [
            'selectedCylinder' => $selectedCylinder,
            'selectedDetail' => $this->cylinderDetail($selectedCylinder),
            'searchKeyword' => $request->string('cylinder')->toString(),
            'lastRemainingGas' => $lastRemainingGas,
            'locations' => Location::where('is_active', true)->orderBy('name')->get(),
            'conditions' => CylinderCondition::cases(),
            'finalStatuses' => [CylinderStatus::Available, CylinderStatus::Empty],
        ]);
    }

    public function storeMaintenanceComplete(StoreMaintenanceCompleteRequest $request): RedirectResponse
    {
        $finalStatus = CylinderStatus::from($request->string('final_status')->toString());

        $outcome = DB::transaction(function () use ($request, $finalStatus) {
            $cylinder = Cylinder::whereKey($request->integer('cylinder_id'))->lockForUpdate()->firstOrFail();

            if ($cylinder->status !== CylinderStatus::Maintenance) {
                return ['rejected' => true, 'cylinder' => $cylinder];
            }

            $notes = "Maintenance selesai - status: {$finalStatus->value}";
            if ($request->filled('notes')) {
                $notes .= " | Catatan: {$request->string('notes')->toString()}";
            }

            $transaction = Transaction::create([
                'cylinder_id' => $cylinder->id,
                'user_id' => $request->user()->id,
                'transaction_type' => TransactionType::Maintenance,
                'from_location_id' => $cylinder->current_location_id,
                'to_location_id' => $request->integer('to_location_id'),
                'condition' => $request->string('condition')->toString(),
                'remaining_gas' => $request->filled('remaining_gas') ? $request->input('remaining_gas') : null,
                'destination' => null,
                'notes' => $notes,
                'transaction_date' => $request->date('transaction_date'),
            ]);

            $cylinder->update([
                'status' => $finalStatus,
                'current_location_id' => $request->integer('to_location_id'),
                'condition' => $request->string('condition')->toString(),
            ]);

            $toName = Location::whereKey($request->integer('to_location_id'))->value('name');
            $this->auditTransaction(
                $request,
                $transaction,
                $cylinder,
                'MAINTENANCE_COMPLETE',
                "Maintenance tabung {$cylinder->cylinder_code} selesai dengan status {$finalStatus->value} di {$toName}.",
                $finalStatus->value,
            );

            return ['rejected' => false, 'cylinder' => $cylinder];
        });

        if ($outcome['rejected']) {
            return back()
                ->withInput()
                ->with('error', 'Tabung tidak sedang dalam proses maintenance.');
        }

        return redirect()
            ->route('cylinders.show', $outcome['cylinder'])
            ->with('success', 'Maintenance tabung berhasil diselesaikan.');
    }
}
