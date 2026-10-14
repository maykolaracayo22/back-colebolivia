<?php

namespace App\Policies;

use App\Models\ActaNote;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ActaNotePolicy
{
    use HandlesAuthorization;

    public function upsert(User $user, ActaNote $note = null)
    {
        if ($user->hasRole('admin')) return true;

        if (!$user->hasPermissionTo('notas.editar')) return false;

        if (!$note) return true; // Crear nueva

        // Verificar ventana de ediciÃ³n
        $acta = $note->acta;
        if ($acta->status === 'PUBLISHED') {
            // Solo admin puede editar despuÃ©s de publicar
            return $user->hasRole('admin');
        }

        if ($acta->edit_window_ends && now()->isAfter($acta->edit_window_ends)) {
            return false; // Ventana cerrada
        }

        // Verificar que el docente es el propietario del acta
        return $acta->docente_id === $user->id;
    }

    public function delete(User $user, ActaNote $note)
    {
        return $this->upsert($user, $note);
    }
    public function before(User $user, string $ability)
{
    if ($user->hasAnyRole(['SuperAdmin', 'Director General', 'admin', 'director_general'])) {
        return true;
    }
    return null;
}

}
