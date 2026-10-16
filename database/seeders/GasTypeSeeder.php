<?php

namespace Database\Seeders;

use App\Models\GasType;
use Illuminate\Database\Seeder;

class GasTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            ['name' => 'Oxygen', 'code' => 'O2', 'description' => 'Gas oksigen untuk pengelasan dan medis.'],
            ['name' => 'Argon', 'code' => 'AR', 'description' => 'Gas argon untuk pengelasan TIG/MIG.'],
            ['name' => 'Carbon Dioxide', 'code' => 'CO2', 'description' => 'Gas karbon dioksida untuk pengelasan MIG dan minuman.'],
            ['name' => 'Nitrogen', 'code' => 'N2', 'description' => 'Gas nitrogen untuk purging dan pengawetan.'],
            ['name' => 'Acetylene', 'code' => 'C2H2', 'description' => 'Gas asetilen untuk pemotongan dan pengelasan oxy-fuel.'],
        ];

        foreach ($types as $data) {
            GasType::updateOrCreate(
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
