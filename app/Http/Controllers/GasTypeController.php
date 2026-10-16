<?php

namespace App\Http\Controllers;

use App\Http\Requests\GasType\StoreGasTypeRequest;
use App\Http\Requests\GasType\UpdateGasTypeRequest;
use App\Models\GasType;
use App\Services\AuditLogService;
use App\Services\Settings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Str;
use Illuminate\View\View;

class GasTypeController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('can:viewAny,'.GasType::class, only: ['index']),
            new Middleware('can:view,gas_type', only: ['show']),
            new Middleware('can:create,'.GasType::class, only: ['create', 'store']),
            new Middleware('can:update,gas_type', only: ['edit', 'update']),
            new Middleware('can:delete,gas_type', only: ['destroy']),
        ];
    }

    public function index(Request $request): View
    {
        $gasTypes = GasType::withCount('cylinders')
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->string('search')->toString();
                $query->where(fn ($q) => $q->where('code', 'like', "%{$search}%")->orWhere('name', 'like', "%{$search}%"));
            })
            ->orderBy('name')
            ->paginate(Settings::perPage())
            ->withQueryString();

        return view('gas-types.index', [
            'gasTypes' => $gasTypes,
            'filters' => $request->only(['search']),
        ]);
    }

    public function create(): View
    {
        return view('gas-types.create');
    }

    public function store(StoreGasTypeRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['code'] = Str::upper($data['code']);
        $data['is_active'] = $request->boolean('is_active');

        $gasType = GasType::create($data);

        AuditLogService::log(
            $request->user(),
            'CREATE_GAS_TYPE',
            'MASTER',
            "Jenis gas {$gasType->code} ditambahkan.",
            $gasType,
            [],
            $gasType->only(['code', 'name', 'description', 'is_active']),
        );

        return redirect()->route('gas-types.show', $gasType)->with('success', 'Jenis gas berhasil ditambahkan.');
    }

    public function show(GasType $gasType): View
    {
        $gasType->loadCount('cylinders');

        return view('gas-types.show', compact('gasType'));
    }

    public function edit(GasType $gasType): View
    {
        return view('gas-types.edit', compact('gasType'));
    }

    public function update(UpdateGasTypeRequest $request, GasType $gasType): RedirectResponse
    {
        $data = $request->validated();
        $data['code'] = Str::upper($data['code']);
        $data['is_active'] = $request->boolean('is_active');

        $old = $gasType->getOriginal();
        $gasType->update($data);

        AuditLogService::log(
            $request->user(),
            'UPDATE_GAS_TYPE',
            'MASTER',
            "Jenis gas {$gasType->code} diperbarui.",
            $gasType,
            collect($old)->only(['code', 'name', 'description', 'is_active'])->all(),
            $gasType->fresh()->only(['code', 'name', 'description', 'is_active']),
        );

        return redirect()->route('gas-types.show', $gasType)->with('success', 'Data jenis gas berhasil diperbarui.');
    }

    public function destroy(Request $request, GasType $gasType): RedirectResponse
    {
        $count = $gasType->cylinders()->count();

        if ($count > 0) {
            return back()->with('error', "Jenis gas {$gasType->name} tidak dapat dihapus karena masih digunakan oleh {$count} tabung.");
        }

        $old = $gasType->only(['name', 'code']);
        $gasType->delete();

        AuditLogService::log(
            $request->user(),
            'DELETE_GAS_TYPE',
            'MASTER',
            "Jenis gas {$old['name']} dihapus.",
            null,
            $old,
            [],
        );

        return redirect()->route('gas-types.index')->with('success', 'Jenis gas berhasil dihapus.');
    }
}
