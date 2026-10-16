<?php

namespace Database\Seeders;

use App\Enums\CylinderCondition;
use App\Enums\CylinderStatus;
use App\Models\Cylinder;
use App\Models\GasType;
use App\Models\Location;
use App\Models\Supplier;
use Illuminate\Database\Seeder;

class CylinderSeeder extends Seeder
{
    public function run(): void
    {
        $gasTypes = GasType::pluck('id', 'code');
        $suppliers = Supplier::pluck('id', 'code');
        $locations = Location::pluck('id', 'code');

        // [code, gas, supplier, capacity, status, condition, location, notes]
        $cylinders = [
            ['GAS-00001', 'O2', 'SUP-001', 6.00, CylinderStatus::Available, CylinderCondition::Good, 'LOC-001', null],
            ['GAS-00002', 'AR', 'SUP-002', 7.50, CylinderStatus::InUse, CylinderCondition::Good, 'LOC-002', 'Dipakai di Welding Area.'],
            ['GAS-00003', 'CO2', 'SUP-001', 10.00, CylinderStatus::Empty, CylinderCondition::Good, 'LOC-001', 'Menunggu pengisian ulang.'],
            ['GAS-00004', 'N2', 'SUP-003', 6.80, CylinderStatus::Maintenance, CylinderCondition::Maintenance, 'LOC-004', 'Sedang inspeksi berkala.'],
            ['GAS-00005', 'C2H2', 'SUP-002', 4.50, CylinderStatus::Outside, CylinderCondition::Good, 'LOC-001', 'Dipinjam pelanggan PT Maju Jaya.'],
            ['GAS-00006', 'O2', 'SUP-002', 6.00, CylinderStatus::InUse, CylinderCondition::Good, 'LOC-003', 'Dipakai di Workshop.'],
            ['GAS-00007', 'AR', 'SUP-001', 7.00, CylinderStatus::Available, CylinderCondition::Good, 'LOC-001', null],
            ['GAS-00008', 'CO2', 'SUP-003', 10.00, CylinderStatus::InUse, CylinderCondition::Good, 'LOC-005', 'Dipakai di Production Area.'],
            ['GAS-00009', 'N2', 'SUP-001', 6.80, CylinderStatus::Empty, CylinderCondition::Damaged, 'LOC-001', 'Valve penyok, perlu perbaikan.'],
            ['GAS-00010', 'C2H2', 'SUP-001', 4.00, CylinderStatus::Available, CylinderCondition::Good, 'LOC-001', null],
            ['GAS-00011', 'O2', 'SUP-003', 6.50, CylinderStatus::InUse, CylinderCondition::Good, 'LOC-005', 'Dipakai di Production Area.'],
            ['GAS-00012', 'AR', 'SUP-002', 7.50, CylinderStatus::Maintenance, CylinderCondition::Damaged, 'LOC-004', 'Badan tabung korosi.'],
            ['GAS-00013', 'CO2', 'SUP-002', 9.50, CylinderStatus::Available, CylinderCondition::Good, 'LOC-001', null],
            ['GAS-00014', 'N2', 'SUP-002', 6.00, CylinderStatus::Outside, CylinderCondition::Good, 'LOC-001', 'Dipakai proyek luar kota.'],
            ['GAS-00015', 'C2H2', 'SUP-003', 4.50, CylinderStatus::Empty, CylinderCondition::Good, 'LOC-001', 'Menunggu pengisian ulang.'],
        ];

        foreach ($cylinders as [$code, $gas, $supplier, $capacity, $status, $condition, $location, $notes]) {
            Cylinder::updateOrCreate(
                ['cylinder_code' => $code],
                [
                    'serial_number' => 'SN-'.$code,
                    'gas_type_id' => $gasTypes[$gas],
                    'supplier_id' => $suppliers[$supplier],
                    'capacity' => $capacity,
                    'unit' => 'm3',
                    'status' => $status,
                    'condition' => $condition,
                    'current_location_id' => $locations[$location],
                    'qr_token' => 'CYL-'.strtoupper(substr(md5($code), 0, 8)),
                    'notes' => $notes,
                ]
            );
        }
    }
}
