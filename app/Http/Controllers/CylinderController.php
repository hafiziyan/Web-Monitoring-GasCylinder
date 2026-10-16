<?php

namespace App\Http\Controllers;

use App\Enums\CylinderCondition;
use App\Enums\CylinderStatus;
use App\Http\Requests\Cylinder\StoreCylinderRequest;
use App\Http\Requests\Cylinder\UpdateCylinderRequest;
use App\Models\Cylinder;
use App\Models\GasType;
use App\Models\Location;
use App\Models\Supplier;
use App\Services\AuditLogService;
use App\Services\CylinderQrCode;
use App\Services\Settings;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;

class CylinderController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('can:viewAny,'.Cylinder::class, only: ['index']),
            new Middleware('can:view,cylinder', only: ['show']),
            new Middleware('can:create,'.Cylinder::class, only: ['create', 'store']),
            new Middleware('can:update,cylinder', only: ['edit', 'update']),
            new Middleware('can:delete,cylinder', only: ['destroy']),
        ];
    }

    public function index(Request $request): View
    {
        $cylinders = Cylinder::with(['gasType', 'supplier', 'currentLocation'])
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->string('search')->toString();
                $query->where(function ($q) use ($search) {
                    $q->where('cylinder_code', 'like', "%{$search}%")
                        ->orWhere('serial_number', 'like', "%{$search}%")
                        ->orWhereHas('gasType', fn ($g) => $g->where('name', 'like', "%{$search}%"));
                });
            })
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')->toString()))
            ->when($request->filled('gas_type_id'), fn ($query) => $query->where('gas_type_id', $request->integer('gas_type_id')))
            ->when($request->filled('current_location_id'), fn ($query) => $query->where('current_location_id', $request->integer('current_location_id')))
            ->orderBy('cylinder_code')
            ->paginate(Settings::perPage())
            ->withQueryString();

        return view('cylinders.index', [
            'cylinders' => $cylinders,
            'gasTypes' => GasType::orderBy('name')->get(),
            'locations' => Location::orderBy('name')->get(),
            'statuses' => CylinderStatus::cases(),
            'filters' => $request->only(['search', 'status', 'gas_type_id', 'current_location_id']),
        ]);
    }

    public function create(): View
    {
        return view('cylinders.create', [
            'gasTypes' => GasType::where('is_active', true)->orderBy('name')->get(),
            'suppliers' => Supplier::where('is_active', true)->orderBy('name')->get(),
            'locations' => Location::where('is_active', true)->orderBy('name')->get(),
            'statuses' => CylinderStatus::cases(),
            'conditions' => CylinderCondition::cases(),
        ]);
    }

    public function store(StoreCylinderRequest $request): RedirectResponse
    {
        $data = $request->validated();
        // Token selalu dibuatkan sistem agar tidak bisa ditebak/di-squat user.
        // Retry untuk tabrakan unik yang sangat jarang terjadi.
        $attempts = 0;
        do {
            $data['qr_token'] = app(CylinderQrCode::class)->generateUniqueToken();
            try {
                $cylinder = Cylinder::create($data);
                break;
            } catch (QueryException $e) {
                if (! $e->getMessage() || ! str_contains($e->getMessage(), 'qr_token')) {
                    throw $e;
                }
                $cylinder = null;
            }
        } while ($cylinder === null && ++$attempts < 3);

        abort_if($cylinder === null, 500, 'Gagal membuat token unik, silakan coba lagi.');

        AuditLogService::log(
            $request->user(),
            'CREATE_CYLINDER',
            'MASTER',
            "Tabung {$cylinder->cylinder_code} ditambahkan.",
            $cylinder,
            [],
            $cylinder->only(['cylinder_code', 'serial_number', 'gas_type_id', 'supplier_id', 'capacity', 'unit', 'status', 'condition', 'current_location_id']),
        );

        return redirect()->route('cylinders.show', $cylinder)->with('success', 'Tabung berhasil ditambahkan.');
    }

    public function show(Cylinder $cylinder): View
    {
        $cylinder->load(['gasType', 'supplier', 'currentLocation']);
        $history = $cylinder->transactions()
            ->with(['user', 'fromLocation', 'toLocation'])
            ->orderByDesc('transaction_date')
            ->get();

        return view('cylinders.show', compact('cylinder', 'history'));
    }

    public function edit(Cylinder $cylinder): View
    {
        return view('cylinders.edit', [
            'cylinder' => $cylinder,
            // Data nonaktif yang sudah terpakai tetap ditampilkan agar tidak hilang.
            'gasTypes' => GasType::where('is_active', true)->orWhere('id', $cylinder->gas_type_id)->orderBy('name')->get(),
            'suppliers' => Supplier::where('is_active', true)->orWhere('id', $cylinder->supplier_id)->orderBy('name')->get(),
            'locations' => Location::where('is_active', true)->orWhere('id', $cylinder->current_location_id)->orderBy('name')->get(),
            'statuses' => CylinderStatus::cases(),
            'conditions' => CylinderCondition::cases(),
        ]);
    }

    public function update(UpdateCylinderRequest $request, Cylinder $cylinder): RedirectResponse
    {
        $old = $cylinder->getOriginal();
        $cylinder->update($request->validated());

        AuditLogService::log(
            $request->user(),
            'UPDATE_CYLINDER',
            'MASTER',
            "Tabung {$cylinder->cylinder_code} diperbarui.",
            $cylinder,
            collect($old)->only(['serial_number', 'gas_type_id', 'supplier_id', 'capacity', 'unit', 'condition'])->all(),
            $cylinder->fresh()->only(['serial_number', 'gas_type_id', 'supplier_id', 'capacity', 'unit', 'condition']),
        );

        return redirect()->route('cylinders.show', $cylinder)->with('success', 'Data tabung berhasil diperbarui.');
    }

    public function destroy(Request $request, Cylinder $cylinder): RedirectResponse
    {
        if ($cylinder->transactions()->exists()) {
            return back()->with('error', 'Tabung tidak dapat dihapus karena sudah memiliki riwayat transaksi.');
        }

        if (in_array($cylinder->status, [CylinderStatus::InUse, CylinderStatus::Outside], true)) {
            return back()->with('error', 'Tabung yang sedang digunakan atau berada di luar tidak dapat dihapus.');
        }

        $old = [
            'cylinder_code' => $cylinder->cylinder_code,
            'serial_number' => $cylinder->serial_number,
            'status' => $cylinder->status->value,
        ];
        $cylinder->delete();

        AuditLogService::log(
            $request->user(),
            'DELETE_CYLINDER',
            'MASTER',
            "Tabung {$old['cylinder_code']} dihapus.",
            null,
            $old,
            [],
        );

        return redirect()->route('cylinders.index')->with('success', 'Tabung berhasil dihapus.');
    }
}
