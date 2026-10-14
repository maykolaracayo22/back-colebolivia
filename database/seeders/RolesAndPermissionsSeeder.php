<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()['cache']->forget('spatie.permission.cache');

        // Define all permissions
        $permissions = [
            // Académico
            'notas.ver',
            'notas.crear',
            'notas.editar',
            'notas.consolidar',
            'notas.rectificar',
            'notas.exportar',
            'evaluaciones.ver',
            'evaluaciones.crear',
            'evaluaciones.editar',
            'evaluaciones.cerrar',
            'actas.ver',
            'actas.crear',
            'actas.editar',
            'actas.publicar',
            'asistencia.ver',
            'asistencia.tomar',
            'asistencia.reportes',
            'conducta.ver',
            'conducta.registrar',
            'conducta.reportes',

            // Financiero
            'finanzas.ver',
            'finanzas.crear',
            'finanzas.editar',
            'finanzas.eliminar',
            'finanzas.pagar',
            'finanzas.anular',
            'finanzas.reportes',

            // RUDE y Preinscripción
            'rude.ver',
            'rude.crear',
            'rude.descargar',
            'preinscripcion.ver',
            'preinscripcion.crear',
            'preinscripcion.aprobar',
            'preinscripcion.rechazar',

            // Estudiantes
            'estudiantes.ver',
            'estudiantes.crear',
            'estudiantes.editar',
            'estudiantes.eliminar',
            'estudiantes.reportes',
            'estudiantes.matricular',
            'estudiantes.promocionar',

            // Docentes
            'docentes.ver',
            'docentes.crear',
            'docentes.editar',
            'docentes.eliminar',
            'docentes.asignar_cursos',

            // Comunicación
            'comunicaciones.ver_historial',
            'comunicaciones.whatsapp.enviar',
            'comunicaciones.plantillas',

            // Administración
            'admin.auditoria',
            'admin.usuarios',
            'admin.roles_permisos',
            'admin.configuracion',
            'admin.backups',
            'promotion.run',
        ];

        // Create permissions
        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Define roles with their permissions
        $roles = [
            'SuperAdmin' => $permissions, // All permissions

            'Director General' => [
                'notas.ver',
                'notas.rectificar',
                'notas.exportar',
                'evaluaciones.ver',
                'evaluaciones.cerrar',
                'actas.ver',
                'actas.publicar',
                'asistencia.reportes',
                'conducta.reportes',
                'finanzas.ver',
                'finanzas.reportes',
                'rude.ver',
                'rude.descargar',
                'preinscripcion.ver',
                'preinscripcion.aprobar',
                'preinscripcion.rechazar',
                'estudiantes.ver',
                'estudiantes.reportes',
                'docentes.ver',
                'comunicaciones.ver_historial',
                'admin.auditoria',
                'promotion.run',
            ],

            'Director Académico' => [
                'notas.ver',
                'notas.rectificar',
                'notas.exportar',
                'evaluaciones.ver',
                'evaluaciones.crear',
                'evaluaciones.editar',
                'evaluaciones.cerrar',
                'actas.ver',
                'actas.crear',
                'actas.editar',
                'actas.publicar',
                'asistencia.ver',
                'asistencia.reportes',
                'conducta.ver',
                'conducta.reportes',
                'estudiantes.ver',
                'estudiantes.reportes',
                'estudiantes.matricular',
                'estudiantes.promocionar',
                'docentes.ver',
                'docentes.asignar_cursos',
                'admin.auditoria',
                'promotion.run',
            ],

            'Secretaría' => [
                'rude.ver',
                'rude.crear',
                'rude.descargar',
                'preinscripcion.ver',
                'preinscripcion.crear',
                'preinscripcion.aprobar',
                'estudiantes.ver',
                'estudiantes.crear',
                'estudiantes.editar',
                'estudiantes.reportes',
                'docentes.ver',
                'comunicaciones.ver_historial',
            ],

            'Regente' => [
                'asistencia.ver',
                'asistencia.tomar',
                'asistencia.reportes',
                'conducta.ver',
                'conducta.registrar',
                'conducta.reportes',
                'estudiantes.ver',
            ],

            'Docente' => [
                'notas.ver',
                'notas.crear',
                'notas.editar',
                'notas.exportar',
                'evaluaciones.ver',
                'evaluaciones.crear',
                'evaluaciones.editar',
                'asistencia.ver',
                'asistencia.tomar',
                'asistencia.reportes',
                'conducta.ver',
            ],

            'Finanzas' => [
                'finanzas.ver',
                'finanzas.crear',
                'finanzas.editar',
                'finanzas.pagar',
                'finanzas.anular',
                'finanzas.reportes',
                'estudiantes.ver',
                'comunicaciones.ver_historial',
            ],

            'Padre' => [
                'notas.ver',
                'notas.exportar',
                'estudiantes.ver',
                'asistencia.reportes',
                'conducta.reportes',
                'finanzas.ver',
            ],

            'Estudiante' => [
                'notas.ver',
                'asistencia.reportes',
                'conducta.reportes',
                'finanzas.ver',
            ],
        ];

        // Create roles and assign permissions
        foreach ($roles as $roleName => $rolePermissions) {
            $role = Role::firstOrCreate(['name' => $roleName]);
            $role->syncPermissions($rolePermissions);
        }
    }
}
