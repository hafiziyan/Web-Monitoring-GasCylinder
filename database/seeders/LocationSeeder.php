<?php

namespace Database\Seeders;

use App\Models\Location;
use Illuminate\Database\Seeder;

class LocationSeeder extends Seeder
{
    public function run(): void
    {
        $locations = [
            ['name' => 'Gudang Gas', 'code' => 'LOC-001', 'description' => 'Gudang penyimpanan utama tabung gas.'],
            ['name' => 'Welding Area', 'code' => 'LOC-002', 'description' => 'Area pengelasan produksi.'],
            ['name' => 'Workshop', 'code' => 'LOC-003', 'description' => 'Bengkel kerja dan perbaikan.'],
            ['name' => 'Maintenance Area', 'code' => 'LOC-004', 'description' => 'Area perawatan dan inspeksi tabung.'],
            ['name' => 'Production Area', 'code' => 'LOC-005', 'description' => 'Area lini produksi.'],
        ];

        foreach ($locations as $data) {
            Location::updateOrCreate(
                ['code' => $data['code']],
                [
                    'name' => $data['name'],
                    'description' => $data['description'],
                    'is_active' => true,
                ]
            );
        }
    }
}
