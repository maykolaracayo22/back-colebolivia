<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Admin user
        $admin = User::firstOrCreate([
            'email' => 'admin@example.com'
        ], [
            'name' => 'Admin',
            'username' => 'admin',
            'password' => Hash::make('password'),
        ]);

        if (method_exists($admin, 'assignRole')) {
            $admin->assignRole('admin');
        }
    }
}

