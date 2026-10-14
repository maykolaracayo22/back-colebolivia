<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Student;
use Illuminate\Support\Facades\Hash;

class StudentUserSeeder extends Seeder
{
    public function run(): void
    {
        $student = Student::first();
        if (!$student) return;

        $email = 'student.demo@example.com';
        $user = User::firstOrCreate(
            ['email'=>$email],
            ['name'=> ($student->nombres ?? 'Alumno').' '.($student->apellidos ?? 'Demo'), 'password'=>Hash::make('password')]
        );

        if (method_exists($user, 'assignRole')) {
            $user->assignRole('student');
        }

        $student->user_id = $user->id;
        $student->save();
    }
}
