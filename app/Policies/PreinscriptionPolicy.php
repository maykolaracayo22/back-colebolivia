<?php

namespace App\Policies;

use App\Models\Preinscription;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class PreinscriptionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(['Admin', 'SuperAdmin', 'Director General', 'secretaria', 'director_academico']);
    }

    public function approve(User $user): bool
    {
        return $user->hasRole(['Admin', 'SuperAdmin', 'Director General', 'secretaria', 'director_academico']);
    }

    public function reject(User $user): bool
    {
        return $user->hasRole(['Admin', 'SuperAdmin', 'Director General', 'secretaria', 'director_academico']);
    }
}
