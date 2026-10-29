<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'owner@scoopshub.test'],
            [
                'name' => 'ScoopsHub Owner',
                'password' => 'Owner123!',
                'role' => 'owner',
            ]
        );

        User::updateOrCreate(
            ['email' => 'manager@scoopshub.test'],
            [
                'name' => 'ScoopsHub Manager',
                'password' => 'Manager123!',
                'role' => 'manager',
            ]
        );

        User::updateOrCreate(
            ['email' => 'cashier@scoopshub.test'],
            [
                'name' => 'ScoopsHub Cashier',
                'password' => 'Cashier123!',
                'role' => 'cashier',
            ]
        );
    }
}
