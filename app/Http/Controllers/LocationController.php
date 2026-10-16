<?php

namespace App\Http\Controllers;

use App\Http\Requests\Location\StoreLocationRequest;
use App\Http\Requests\Location\UpdateLocationRequest;
use App\Models\Location;
use App\Models\Transaction;
use App\Services\AuditLogService;
use App\Services\Settings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;

class LocationController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('can:viewAny,'.Location::class, only: ['index']),
            new Middleware('can:view,location', only: ['show']),
            new Middleware('can:create,'.Location::class, only: ['create', 'store']),
            new Middleware('can:update,location', only: ['edit', 'update']),
            new Middleware('can:delete,location', only: ['destroy']),
        ];
    }

    public function index(Request $request): View
    {
        $locations = Location::withCount('cylinders')
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->string('search')->toString();
                $query->where(fn ($q) => $q->where('code', 'like', "%{$search}%")->orWhere('name', 'like', "%{$search}%"));
            })
            ->orderBy('name')
            ->paginate(Settings::perPage())
            ->withQueryString();

        return view('locations.index', [
            'locations' => $locations,
            'filters' => $request->only(['search']),
        ]);
    }

    public function create(): View
    {
        return view('locations.create');
    }

    public function store(StoreLocationRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active');

        $location = Location::create($data);

        AuditLogService::log(
            $request->user(),
            'CREATE_LOCATION',
            'MASTER',
            "Lokasi {$location->code} ditambahkan.",
            $location,
            [],
            $location->only(['code', 'name', 'description', 'is_active']),
        );

        return redirect()->route('locations.show', $location)->with('success', 'Lokasi berhasil ditambahkan.');
    }

    public function show(Location $location): View
    {
        $location->loadCount('cylinders');

        return view('locations.show', compact('location'));
    }

    public function edit(Location $location): View
    {
        return view('locations.edit', compact('location'));
    }

    public function update(UpdateLocationRequest $request, Location $location): RedirectResponse
    {
        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active');

        $old = $location->getOriginal();
        $location->update($data);

        AuditLogService::log(
            $request->user(),
            'UPDATE_LOCATION',
            'MASTER',
            "Lokasi {$location->code} diperbarui.",
            $location,
            collect($old)->only(['code', 'name', 'description', 'is_active'])->all(),
            $location->fresh()->only(['code', 'name', 'description', 'is_active']),
        );

        return redirect()->route('locations.show', $location)->with('success', 'Data lokasi berhasil diperbarui.');
    }

    public function destroy(Request $request, Location $location): RedirectResponse
    {
        $usedByCylinders = $location->cylinders()->exists();
        $usedByTransactions = Transaction::where('from_location_id', $location->id)
            ->orWhere('to_location_id', $location->id)
            ->exists();

        if ($usedByCylinders || $usedByTransactions) {
            return back()->with('error', 'Lokasi tidak dapat dihapus karena masih digunakan dalam data tabung atau riwayat transaksi.');
        }

        $old = $location->only(['name', 'code']);
        $location->delete();

        AuditLogService::log(
            $request->user(),
            'DELETE_LOCATION',
            'MASTER',
            "Lokasi {$old['name']} dihapus.",
            null,
            $old,
            [],
        );

        return redirect()->route('locations.index')->with('success', 'Lokasi berhasil dihapus.');
    }
}
