<?php

namespace App\Policies;

use App\Models\StudentFee;
use App\Models\User;

class StudentFeePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(['admin', 'finanzas', 'director_general']);
    }

    public function view(User $user, StudentFee $studentFee): bool
    {
        return $this->viewAny($user) ||
               ($user->hasRole('student') && $studentFee->student->user_id === $user->id) ||
               ($user->hasRole('parent') && $studentFee->student->tutor_ci === optional($user->parent)->ci);
    }

    public function create(User $user): bool
    {
        return $user->hasRole(['admin', 'finanzas']);
    }

    public function update(User $user, StudentFee $studentFee): bool
    {
        return $this->create($user);
    }

    public function delete(User $user, StudentFee $studentFee): bool
    {
        return $user->hasRole('admin');
    }
}
