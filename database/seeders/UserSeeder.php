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
                'name' => 'Administrator UPZ',
                'username' => 'admin',
                'email' => 'admin@baznas-pkg.id',
                'password' => Hash::make('admin123'),
                'role' => 'admin',
                'no_hp' => '082264904535',
                'nip' => 'UPZ-001',
            ],
            [
                'name' => 'Bendahara UPZ',
                'username' => 'bendahara',
                'email' => 'bendahara@baznas-pkg.id',
                'password' => Hash::make('bendahara123'),
                'role' => 'bendahara',
                'no_hp' => '081234567891',
                'nip' => 'UPZ-002',
            ],
            [
                'name' => 'Ketua UPZ',
                'username' => 'ketua',
                'email' => 'ketua@baznas-pkg.id',
                'password' => Hash::make('ketua123'),
                'role' => 'ketua',
                'no_hp' => '081234567892',
                'nip' => 'UPZ-003',
            ],
            [
                'name' => 'Ahmad Muzakki',
                'username' => 'donatur',
                'email' => 'donatur@gmail.com',
                'password' => Hash::make('donatur123'),
                'role' => 'user',
                'no_hp' => '081234567893',
                'nip' => 'PG-88219',
            ],
        ];

        foreach ($users as $u) {
            User::updateOrCreate(['username' => $u['username']], $u);
        }
    }
}
