<?php

namespace App\Http\Controllers;

use App\Enums\CylinderStatus;
use App\Models\Cylinder;
use App\Models\GasType;
use App\Models\Location;
use App\Services\AuditLogService;
use App\Services\CylinderQrCode;
use App\Services\Settings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class QrCodeController extends Controller
{
    public function __construct(private CylinderQrCode $qrCode) {}

    public function index(Request $request): View
    {
        $cylinders = Cylinder::with(['gasType', 'currentLocation'])
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->string('search')->toString();
                $query->where(fn ($q) => $q->where('cylinder_code', 'like', "%{$search}%")
                    ->orWhere('serial_number', 'like', "%{$search}%"));
            })
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')->toString()))
            ->when($request->filled('gas_type_id'), fn ($query) => $query->where('gas_type_id', $request->integer('gas_type_id')))
            ->when($request->filled('current_location_id'), fn ($query) => $query->where('current_location_id', $request->integer('current_location_id')))
            ->orderBy('cylinder_code')
            ->paginate(Settings::perPage())
            ->withQueryString();

        return view('qr-codes.index', [
            'cylinders' => $cylinders,
            'gasTypes' => GasType::orderBy('name')->get(),
            'locations' => Location::orderBy('name')->get(),
            'statuses' => CylinderStatus::cases(),
            'filters' => $request->only(['search', 'status', 'gas_type_id', 'current_location_id']),
        ]);
    }

    public function preview(Cylinder $cylinder): View
    {
        $cylinder->load(['gasType', 'currentLocation']);

        return view('qr-codes.preview', [
            'cylinder' => $cylinder,
            'qrSvg' => $cylinder->qr_token ? $this->qrCode->svg($cylinder->qr_token) : null,
        ]);
    }

    public function print(Cylinder $cylinder): View
    {
        abort_if(! $cylinder->qr_token, 422, 'Cylinder belum memiliki QR token.');

        $cylinder->load(['gasType']);

        AuditLogService::log(
            request()->user(),
            'PRINT_QR',
            'QR',
            "QR Code untuk {$cylinder->cylinder_code} dicetak.",
            $cylinder,
        );

        return view('qr-codes.print', [
            'cylinders' => collect([$cylinder]),
            'qrImages' => [$cylinder->id => $this->qrCode->svg($cylinder->qr_token)],
        ]);
    }

    public function printSelected(Request $request): View
    {
        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:cylinders,id'],
        ]);

        $cylinders = Cylinder::with(['gasType'])
            ->whereIn('id', $validated['ids'])
            ->whereNotNull('qr_token')
            ->orderBy('cylinder_code')
            ->get();

        abort_if($cylinders->isEmpty(), 422, 'Tidak ada cylinder terpilih yang memiliki QR token.');

        AuditLogService::log(
            $request->user(),
            'PRINT_QR',
            'QR',
            "QR Code untuk {$cylinders->count()} tabung dicetak: {$cylinders->pluck('cylinder_code')->implode(', ')}.",
        );

        $qrImages = [];
        foreach ($cylinders as $cylinder) {
            $qrImages[$cylinder->id] = $this->qrCode->svg($cylinder->qr_token);
        }

        return view('qr-codes.print', compact('cylinders', 'qrImages'));
    }

    /**
     * Generate token hanya untuk cylinder yang belum memilikinya.
     * Token yang sudah ada tidak pernah diubah di sini.
     */
    public function generate(Cylinder $cylinder): RedirectResponse
    {
        if ($cylinder->qr_token) {
            return back()->with('info', 'Cylinder sudah memiliki QR token, tidak ada perubahan.');
        }

        $cylinder->update(['qr_token' => $this->qrCode->generateUniqueToken()]);

        AuditLogService::log(
            request()->user(),
            'GENERATE_QR',
            'QR',
            "QR Code untuk {$cylinder->cylinder_code} dibuat.",
            $cylinder,
        );

        return back()->with('success', 'QR token berhasil dibuat.');
    }
}
