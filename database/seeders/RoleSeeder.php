<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        // limpiar cache de permisos
        app()['cache']->forget('spatie.permission.cache');

        /**
         * ROLES OFICIALES DEL SISTEMA
         */
        $roles = [
            'admin',
            'director_general',
            'director_academico',
            'secretaria',
            'regente',
            'finanzas',
            'teacher',
            'student',
            'parent',
        ];

        foreach ($roles as $r) {
            Role::firstOrCreate([
                'name' => $r,
                'guard_name' => 'web'
            ]);
        }

        /**
         * PERMISOS
         */
        $permissions = [
            // Gestión Académica
            'students.manage',
            'teachers.manage',
            'sections.manage',
            'subjects.manage',
            'assignments.manage',

            // Libro de Calificaciones / Actas / Calificaciones
            'actas.create',
            'actas.edit',
            'actas.lock',
            'actas.publish',
            'notes.upsert',
            'grades.centralize',
            'plans.approve',

            // Secretaría
            'prereg.manage',
            'rude.upload',
            'rude.centralize',
            'parentmsg.send',

            // Regente
            'lists.read',
            'conduct.manage',
            'attendance.manage',

            // Finanzas
            'fees.read',
            'fees.write',

            // Administración y Auditoría
            'audit.view',
            'export.generate',
            'notices.write',
            'promotion.run',
        ];

        foreach ($permissions as $p) {
            Permission::firstOrCreate([
                'name'       => $p,
                'guard_name' => 'web'
            ]);
        }

        /**
         * ASIGNACIÓN POR ROL
         */
        Role::findByName('admin')->syncPermissions($permissions);

        Role::findByName('director_general')->syncPermissions($permissions); // control total académico y administrativo

        Role::findByName('director_academico')->syncPermissions([
            'grades.centralize',
            'plans.approve',
            'notices.write',
            'export.generate',
            'audit.view',
            'lists.read',
            'attendance.manage'
        ]);

        Role::findByName('secretaria')->syncPermissions([
            'prereg.manage',
            'rude.upload',
            'rude.centralize',
            'parentmsg.send',
            'export.generate'
        ]);

        Role::findByName('regente')->syncPermissions([
            'lists.read',
            'conduct.manage',
            'attendance.manage'
        ]);

        Role::findByName('finanzas')->syncPermissions([
            'fees.read',
            'fees.write',
            'export.generate'
        ]);

        Role::findByName('teacher')->syncPermissions([
            'actas.create',
            'actas.edit',
            'actas.lock',
            'notes.upsert',
            'attendance.manage'
        ]);

        // student / parent: accesos solamente vía policies según contexto
        Role::findByName('student')->syncPermissions([]);
        Role::findByName('parent')->syncPermissions([]);
    }
}
