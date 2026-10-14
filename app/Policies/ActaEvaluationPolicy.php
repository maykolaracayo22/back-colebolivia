<?php

namespace App\Policies;

use App\Models\ActaEvaluation;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ActaEvaluationPolicy
{
    use HandlesAuthorization;

    public function create(User $user)
    {
        return $user->hasRole('admin') || $user->hasPermissionTo('actas.create') || $user->hasPermissionTo('actas.edit');
    }

    public function update(User $user, ActaEvaluation $evaluation)
    {
        // Admin always allowed
        if ($user->hasRole('admin')) return true;

        // Allow teacher only if they own the parent acta
        $acta = $evaluation->acta()->first();
        if ($user->hasRole('teacher') && optional($user->teacher)->id === optional($acta)->teacher_id) return true;

        return false;
    }

    public function delete(User $user, ActaEvaluation $evaluation)
    {
        return $this->update($user, $evaluation);
    }
    public function before(User $user, string $ability)
{
    if ($user->hasAnyRole(['SuperAdmin', 'Director General', 'admin', 'director_general'])) {
        return true;
    }
    return null;
}

}
