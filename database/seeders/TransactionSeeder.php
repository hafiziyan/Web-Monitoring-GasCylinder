<?php

namespace Database\Seeders;

use App\Enums\TransactionType;
use App\Models\Cylinder;
use App\Models\Location;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Seeder;

class TransactionSeeder extends Seeder
{
    public function run(): void
    {
        $cylinders = Cylinder::pluck('id', 'cylinder_code');
        $users = User::pluck('id', 'email');
        $locations = Location::pluck('id', 'code');

        $admin = $users['admin@gascylinder.test'];
        $op1 = $users['operator1@gascylinder.test'];
        $op2 = $users['operator2@gascylinder.test'];
        $gudang = $locations['LOC-001'];
        $welding = $locations['LOC-002'];
        $workshop = $locations['LOC-003'];
        $maintenance = $locations['LOC-004'];
        $production = $locations['LOC-005'];

        // Riwayat berakhir sesuai status/lokasi akhir di CylinderSeeder.
        $history = [
            // GAS-00001: IN -> OUT -> RETURN (akhir: AVAILABLE di Gudang)
            ['GAS-00001', $admin, TransactionType::In, null, $gudang, '2026-09-10 08:00:00', 'GOOD', 6.00, null, 'Tabung baru masuk dari supplier.'],
            ['GAS-00001', $op1, TransactionType::Out, $gudang, $welding, '2026-09-12 09:00:00', 'GOOD', 6.00, null, 'Dikeluarkan untuk pengelasan.'],
            ['GAS-00001', $op1, TransactionType::Return, $welding, $gudang, '2026-09-20 15:00:00', 'GOOD', 2.50, null, 'Kembali ke gudang, sisa gas 2.5 m3.'],

            // GAS-00002: IN -> OUT (akhir: IN_USE di Welding Area)
            ['GAS-00002', $admin, TransactionType::In, null, $gudang, '2026-09-11 08:00:00', 'GOOD', 7.50, null, 'Tabung baru masuk dari supplier.'],
            ['GAS-00002', $op2, TransactionType::Out, $gudang, $welding, '2026-09-15 10:00:00', 'GOOD', 7.50, null, 'Dikeluarkan untuk pengelasan.'],

            // GAS-00003: IN -> OUT -> RETURN kosong (akhir: EMPTY di Gudang)
            ['GAS-00003', $admin, TransactionType::In, null, $gudang, '2026-09-05 08:00:00', 'GOOD', 10.00, null, 'Tabung baru masuk dari supplier.'],
            ['GAS-00003', $op1, TransactionType::Out, $gudang, $production, '2026-09-08 09:00:00', 'GOOD', 10.00, null, 'Dikeluarkan untuk produksi.'],
            ['GAS-00003', $op2, TransactionType::Return, $production, $gudang, '2026-09-25 14:00:00', 'GOOD', 0.00, null, 'Kembali dalam keadaan kosong.'],

            // GAS-00004: IN -> MAINTENANCE (akhir: MAINTENANCE di Maintenance Area)
            ['GAS-00004', $admin, TransactionType::In, null, $gudang, '2026-09-01 08:00:00', 'GOOD', 6.80, null, 'Tabung baru masuk dari supplier.'],
            ['GAS-00004', $op1, TransactionType::Maintenance, $gudang, $maintenance, '2026-09-22 10:00:00', 'MAINTENANCE', null, null, 'Masuk inspeksi berkala.'],

            // GAS-00005: IN -> OUT ke eksternal (akhir: OUTSIDE, catatan di cylinder)
            ['GAS-00005', $admin, TransactionType::In, null, $gudang, '2026-09-06 08:00:00', 'GOOD', 4.50, null, 'Tabung baru masuk dari supplier.'],
            ['GAS-00005', $op1, TransactionType::Out, $gudang, null, '2026-09-26 09:00:00', 'GOOD', 4.50, 'PT Maju Jaya', 'Dipinjam pelanggan.'],

            // GAS-00006: IN -> TRANSFER (akhir: IN_USE di Workshop)
            ['GAS-00006', $admin, TransactionType::In, null, $gudang, '2026-09-13 08:00:00', 'GOOD', 6.00, null, 'Tabung baru masuk dari supplier.'],
            ['GAS-00006', $op2, TransactionType::Transfer, $gudang, $workshop, '2026-09-18 11:00:00', 'GOOD', 6.00, null, 'Dipindahkan ke Workshop.'],

            // GAS-00012: IN -> MAINTENANCE rusak (akhir: MAINTENANCE/DAMAGED di Maintenance Area)
            ['GAS-00012', $admin, TransactionType::In, null, $gudang, '2026-09-02 08:00:00', 'GOOD', 7.50, null, 'Tabung baru masuk dari supplier.'],
            ['GAS-00012', $op2, TransactionType::Maintenance, $gudang, $maintenance, '2026-09-21 13:00:00', 'DAMAGED', null, null, 'Ditemukan korosi pada badan tabung.'],
        ];

        foreach ($history as [$code, $userId, $type, $from, $to, $date, $condition, $remaining, $destination, $notes]) {
            Transaction::updateOrCreate(
                [
                    'cylinder_id' => $cylinders[$code],
                    'transaction_type' => $type,
                    'transaction_date' => $date,
                ],
                [
                    'user_id' => $userId,
                    'from_location_id' => $from,
                    'to_location_id' => $to,
                    'condition' => $condition,
                    'remaining_gas' => $remaining,
                    'destination' => $destination,
                    'notes' => $notes,
                ]
            );
        }
    }
}
