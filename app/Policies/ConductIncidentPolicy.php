<?php

namespace App\Policies;

use App\Models\ConductIncident;
use App\Models\User;

class ConductIncidentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(['Admin', 'SuperAdmin', 'Director General', 'regente', 'director_academico']);
    }

    public function view(User $user, ConductIncident $incident): bool
    {
        return $this->viewAny($user) ||
               ($user->hasRole('teacher') && $incident->reported_by === $user->id) ||
               ($user->hasRole('student') && $incident->student->user_id === $user->id) ||
               ($user->hasRole('parent') && $incident->student->tutor_ci === optional($user->parent)->ci);
    }

    public function create(User $user): bool
    {
        return $user->hasRole(['Admin', 'SuperAdmin', 'Director General', 'regente', 'teacher']);
    }

    public function update(User $user, ConductIncident $incident): bool
    {
        return $this->create($user) && $incident->reported_by === $user->id ||
               $user->hasRole(['Admin', 'SuperAdmin', 'Director General', 'regente']);
    }

    public function approve(User $user, ConductIncident $incident): bool
    {
        return $user->hasRole(['Admin', 'SuperAdmin', 'Director General', 'director_academico']);
    }

    public function delete(User $user, ConductIncident $incident): bool
    {
        return $user->hasRole(['Admin', 'SuperAdmin', 'Director General']) ||
               ($user->hasRole('regente') && $incident->reported_by === $user->id);
    }
}
