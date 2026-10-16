<?php

namespace Database\Seeders;

use App\Models\Supplier;
use Illuminate\Database\Seeder;

class SupplierSeeder extends Seeder
{
    public function run(): void
    {
        $suppliers = [
            [
                'name' => 'PT Gasindo Supplier',
                'code' => 'SUP-001',
                'contact_person' => 'Budi Santoso',
                'phone' => '021-5550123',
                'address' => 'Jl. Raya Industri No. 10, Cikarang, Bekasi',
            ],
            [
                'name' => 'PT Aneka Gas Industri',
                'code' => 'SUP-002',
                'contact_person' => 'Siti Rahayu',
                'phone' => '021-5550456',
                'address' => 'Jl. Merdeka No. 25, Karawang, Jawa Barat',
            ],
            [
                'name' => 'CV Mitra Gas',
                'code' => 'SUP-003',
                'contact_person' => 'Andi Wijaya',
                'phone' => '022-5550789',
                'address' => 'Jl. Soekarno Hatta No. 88, Bandung, Jawa Barat',
            ],
        ];

        foreach ($suppliers as $data) {
            Supplier::updateOrCreate(
                ['code' => $data['code']],
                [
                    'name' => $data['name'],
                    'contact_person' => $data['contact_person'],
                    'phone' => $data['phone'],
                    'address' => $data['address'],
                    'is_active' => true,
                ]
            );
        }
    }
}
