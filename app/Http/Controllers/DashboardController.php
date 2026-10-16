<?php

namespace App\Http\Controllers;

use App\Enums\CylinderCondition;
use App\Enums\CylinderStatus;
use App\Models\Cylinder;
use App\Models\GasType;
use App\Models\Location;
use App\Models\Transaction;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $statusCounts = Cylinder::query()
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $stats = [
            'total' => Cylinder::count(),
            'available' => (int) ($statusCounts[CylinderStatus::Available->value] ?? 0),
            'in_use' => (int) ($statusCounts[CylinderStatus::InUse->value] ?? 0),
            'empty' => (int) ($statusCounts[CylinderStatus::Empty->value] ?? 0),
            'maintenance' => (int) ($statusCounts[CylinderStatus::Maintenance->value] ?? 0),
            'outside' => (int) ($statusCounts[CylinderStatus::Outside->value] ?? 0),
        ];

        $conditionCounts = Cylinder::query()
            ->selectRaw('`condition`, COUNT(*) as total')
            ->groupBy('condition')
            ->pluck('total', 'condition');

        $conditions = [
            'good' => (int) ($conditionCounts[CylinderCondition::Good->value] ?? 0),
            'damaged' => (int) ($conditionCounts[CylinderCondition::Damaged->value] ?? 0),
            'maintenance' => (int) ($conditionCounts[CylinderCondition::Maintenance->value] ?? 0),
        ];

        $gasDistribution = GasType::query()
            ->withCount('cylinders')
            ->orderBy('name')
            ->get();

        $locationSummary = Location::query()
            ->withCount('cylinders')
            ->orderBy('name')
            ->get();

        $topActions = Transaction::query()
            ->selectRaw('transaction_type, COUNT(*) as total')
            ->groupBy('transaction_type')
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        $latestActivities = Transaction::query()
            ->with(['cylinder.gasType', 'user', 'fromLocation', 'toLocation'])
            ->orderByDesc('transaction_date')
            ->limit(8)
            ->get();

        return view('dashboard', compact(
            'stats', 'conditions', 'gasDistribution', 'locationSummary', 'topActions', 'latestActivities'
        ));
    }
}
