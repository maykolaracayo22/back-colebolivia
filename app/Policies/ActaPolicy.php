<?php

namespace App\Policies;

use App\Models\Acta;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ActaPolicy
{
    use HandlesAuthorization;

    /**
     * Super permisos antes de cualquier chequeo.
     * Admin y Director General pueden todo.
     */
    public function before(User $user, string $ability)
    {
        // OJO: nombres EXACTOS de roles según tu seeder
        if ($user->hasAnyRole(['Admin', 'Director General',        'admin',
        'director_general',])) {
            return true;
        }

        return null; // sigue con los otros métodos
    }

    public function viewAny(User $user): bool
    {
        // Pueden listar: quienes tienen permiso de ver actas
        // o los roles académicos principales
        return
            $user->can('actas.ver') ||
            $user->hasAnyRole([
                'Director Académico',
                'teacher',
                'Docente',
                'student',
                'Estudiante',
                'parent',
                'Padre',
                'Secretaria',
                'Regente',
            ]);
    }

    public function view(User $user, Acta $acta): bool
    {
        // Director Académico o cualquier usuario con permiso de ver actas
        if ($user->can('actas.ver') || $user->hasRole('Director Académico')) {
            return true;
        }

        // Docente dueño del acta
        if (
            $user->hasAnyRole(['teacher', 'Docente']) &&
            (int) ($user->teacher_id ?? optional($user->teacher)->id) === (int) $acta->docente_id
        ) {
            return true;
        }

        // Lectura para estudiante/padre/secretaria/regente (el filtro por alumno lo hace el controller/consulta)
        if ($user->hasAnyRole(['Estudiante', 'Padre', 'Secretaria', 'Regente'])) {
            return true;
        }

        return false;
    }

    public function create(User $user): bool
    {
        // Director Académico (tiene actas.crear)
        // Docente (tiene notas.crear)
        return
            $user->can('actas.crear') ||
            $user->can('notas.crear');
    }

    public function update(User $user, Acta $acta): bool
    {
        // Solo se edita mientras esté en borrador
        if ($acta->status !== 'DRAFT') {
            return false;
        }

        // Director Académico con permiso de editar actas
        if ($user->hasRole('Director Académico') && $user->can('actas.editar')) {
            return true;
        }

        // El docente solo puede editar el acta de su propio perfil.
        // La propiedad del acta es el límite de acceso; no depende de un permiso global
        // que puede faltar en usuarios creados con el alias de rol "teacher".
        if (
            $user->hasAnyRole(['teacher', 'Docente']) &&
            (int) ($user->teacher_id ?? optional($user->teacher)->id) === (int) $acta->docente_id
        ) {
            return true;
        }

        return false;
    }

    public function delete(User $user, Acta $acta): bool
    {
        // Solo Admin / Director General (ya manejado en before)
        return false;
    }

    public function lock(User $user, Acta $acta): bool
    {
        // Solo se puede bloquear si está en borrador
        if ($acta->status !== 'DRAFT') {
            return false;
        }

        // Director Académico con permiso de cerrar evaluaciones
        if ($user->hasRole('Director Académico') && $user->can('evaluaciones.cerrar')) {
            return true;
        }

        // Docente dueño del acta con permiso de cerrar evaluaciones
        if (
            $user->hasAnyRole(['teacher', 'Docente']) &&
            (int) ($user->teacher_id ?? optional($user->teacher)->id) === (int) $acta->docente_id &&
            $user->can('evaluaciones.cerrar')
        ) {
            return true;
        }

        return false;
    }

    public function publish(User $user, Acta $acta): bool
    {
        // Solo se publica desde LOCKED
        if ($acta->status !== 'LOCKED') {
            return false;
        }

        // Director Académico / Admin con permiso de publicar o consolidar
        return
            $user->can('actas.publicar') ||
            $user->can('notas.consolidar');
    }
}
