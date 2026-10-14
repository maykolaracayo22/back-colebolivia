<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class DemoUsersByRoleSeeder extends Seeder
{
    public function run(): void
    {
        $rows = [
            ['name'=>'Super Admin','email'=>'admin@example.com','role'=>'admin','username'=>'admin'],
            ['name'=>'Director General','email'=>'dg@example.com','role'=>'director_general','username'=>'dg'],
            ['name'=>'Director Académico','email'=>'da@example.com','role'=>'director_academico','username'=>'da'],
            ['name'=>'Secretaría','email'=>'secretaria@example.com','role'=>'secretaria','username'=>'secretaria'],
            ['name'=>'Regente','email'=>'regente@example.com','role'=>'regente','username'=>'regente'],
            ['name'=>'Finanzas','email'=>'finanzas@example.com','role'=>'finanzas','username'=>'finanzas'],
            // si ya creaste teachers/students/parents por otros seeders, puedes omitir estos:
            ['name'=>'Docente Demo','email'=>'docente@example.com','role'=>'teacher','username'=>'docente'],
            ['name'=>'Estudiante Demo','email'=>'estudiante@example.com','role'=>'student','username'=>'estudiante'],
            ['name'=>'Padre Demo','email'=>'padre@example.com','role'=>'parent','username'=>'padre'],
        ];

        foreach ($rows as $r) {
            $user = User::firstOrCreate(
                ['email' => $r['email']],
                [
                    'name' => $r['name'],
                    'username' => $r['username'],
                    'password' => Hash::make('password')
                ]
            );
            if (method_exists($user,'syncRoles')) {
                $user->syncRoles([$r['role']]);
            }
        }
    }
}
