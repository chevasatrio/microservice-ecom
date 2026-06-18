<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            [
                'id'       => 'a1111111-1111-1111-1111-111111111111',
                'name'     => 'Budi Santoso',
                'email'    => 'budi@example.com',
                'password' => Hash::make('password123'),
            ],
            [
                'id'       => 'a2222222-2222-2222-2222-222222222222',
                'name'     => 'Siti Aminah',
                'email'    => 'siti@example.com',
                'password' => Hash::make('password123'),
            ],
            [
                'id'       => 'a3333333-3333-3333-3333-333333333333',
                'name'     => 'Andi Wijaya',
                'email'    => 'andi@example.com',
                'password' => Hash::make('password123'),
            ],
            [
                'id'       => 'a4444444-4444-4444-4444-444444444444',
                'name'     => 'Dewi Lestari',
                'email'    => 'dewi@example.com',
                'password' => Hash::make('password123'),
            ],
            [
                'id'       => 'a5555555-5555-5555-5555-555555555555',
                'name'     => 'Rian Pratama',
                'email'    => 'rian@example.com',
                'password' => Hash::make('password123'),
            ],
        ];

        foreach ($users as $user) {
            User::updateOrCreate(['id' => $user['id']], $user);
        }
    }
}
