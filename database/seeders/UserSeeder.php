<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            [
                'name' => 'Admin Gas',
                'email' => 'admin@gascylinder.test',
                'role' => UserRole::Admin,
            ],
            [
                'name' => 'Operator 1',
                'email' => 'operator1@gascylinder.test',
                'role' => UserRole::Operator,
            ],
            [
                'name' => 'Operator 2',
                'email' => 'operator2@gascylinder.test',
                'role' => UserRole::Operator,
            ],
        ];

        foreach ($users as $data) {
            User::updateOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $data['name'],
                    'password' => Hash::make('password'),
                    'role' => $data['role'],
                    'is_active' => true,
                ]
            );
        }
    }
}
