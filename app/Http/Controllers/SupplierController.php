<?php

namespace App\Http\Controllers;

use App\Http\Requests\Supplier\StoreSupplierRequest;
use App\Http\Requests\Supplier\UpdateSupplierRequest;
use App\Models\Supplier;
use App\Services\AuditLogService;
use App\Services\Settings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;

class SupplierController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('can:viewAny,'.Supplier::class, only: ['index']),
            new Middleware('can:view,supplier', only: ['show']),
            new Middleware('can:create,'.Supplier::class, only: ['create', 'store']),
            new Middleware('can:update,supplier', only: ['edit', 'update']),
            new Middleware('can:delete,supplier', only: ['destroy']),
        ];
    }

    public function index(Request $request): View
    {
        $suppliers = Supplier::withCount('cylinders')
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->string('search')->toString();
                $query->where(fn ($q) => $q->where('code', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('contact_person', 'like', "%{$search}%"));
            })
            ->orderBy('name')
            ->paginate(Settings::perPage())
            ->withQueryString();

        return view('suppliers.index', [
            'suppliers' => $suppliers,
            'filters' => $request->only(['search']),
        ]);
    }

    public function create(): View
    {
        return view('suppliers.create');
    }

    public function store(StoreSupplierRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active');

        $supplier = Supplier::create($data);

        AuditLogService::log(
            $request->user(),
            'CREATE_SUPPLIER',
            'MASTER',
            "Supplier {$supplier->code} ditambahkan.",
            $supplier,
            [],
            $supplier->only(['code', 'name', 'contact_person', 'phone', 'address', 'is_active']),
        );

        return redirect()->route('suppliers.show', $supplier)->with('success', 'Supplier berhasil ditambahkan.');
    }

    public function show(Supplier $supplier): View
    {
        $supplier->loadCount('cylinders');

        return view('suppliers.show', compact('supplier'));
    }

    public function edit(Supplier $supplier): View
    {
        return view('suppliers.edit', compact('supplier'));
    }

    public function update(UpdateSupplierRequest $request, Supplier $supplier): RedirectResponse
    {
        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active');

        $old = $supplier->getOriginal();
        $supplier->update($data);

        AuditLogService::log(
            $request->user(),
            'UPDATE_SUPPLIER',
            'MASTER',
            "Supplier {$supplier->code} diperbarui.",
            $supplier,
            collect($old)->only(['code', 'name', 'contact_person', 'phone', 'address', 'is_active'])->all(),
            $supplier->fresh()->only(['code', 'name', 'contact_person', 'phone', 'address', 'is_active']),
        );

        return redirect()->route('suppliers.show', $supplier)->with('success', 'Data supplier berhasil diperbarui.');
    }

    public function destroy(Request $request, Supplier $supplier): RedirectResponse
    {
        if ($supplier->cylinders()->exists()) {
            return back()->with('error', 'Supplier tidak dapat dihapus karena masih digunakan oleh data tabung.');
        }

        $old = $supplier->only(['name', 'code']);
        $supplier->delete();

        AuditLogService::log(
            $request->user(),
            'DELETE_SUPPLIER',
            'MASTER',
            "Supplier {$old['name']} dihapus.",
            null,
            $old,
            [],
        );

        return redirect()->route('suppliers.index')->with('success', 'Supplier berhasil dihapus.');
    }
}
