<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermisosNotasSeeder extends Seeder
{
    public function run(): void
    {
        // Permissions
        $perms = [
            'notas.ver',
            'notas.editar',
            'notas.consolidar',
            'notas.rectificar',
        ];

        foreach ($perms as $p) {
            Permission::firstOrCreate(['name' => $p]);
        }

        // Roles
        $docente = Role::firstOrCreate(['name' => 'Docente']);
        $dirAcademico = Role::firstOrCreate(['name' => 'director_academico']);
        $dirGeneral = Role::firstOrCreate(['name' => 'Director General']);
        $super = Role::firstOrCreate(['name' => 'SuperAdmin']);

        // Assign permissions
        $docente->givePermissionTo(['notas.ver','notas.editar']);
        $dirAcademico->givePermissionTo(['notas.ver','notas.consolidar','notas.rectificar']);
        $dirGeneral->givePermissionTo(['notas.ver','notas.editar','notas.consolidar','notas.rectificar']);
        $super->givePermissionTo(Permission::all());
    }
}
