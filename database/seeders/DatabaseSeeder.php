<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            GasTypeSeeder::class,
            SupplierSeeder::class,
            LocationSeeder::class,
            CylinderSeeder::class,
            TransactionSeeder::class,
        ]);
    }
}
