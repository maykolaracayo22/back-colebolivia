<?php

namespace App\Policies;

use App\Models\Attendance;
use App\Models\User;

class AttendancePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(['Admin', 'SuperAdmin', 'Director General', 'teacher', 'Docente', 'regente', 'director_academico'])
            || $user->isTeacher();
    }

    public function view(User $user, Attendance $attendance): bool
    {
        return $this->viewAny($user) ||
               ($user->hasRole('student') && $attendance->student->user_id === $user->id) ||
               ($user->hasRole('parent') && $attendance->student->tutor_ci === optional($user->parent)->ci);
    }

    public function create(User $user): bool
    {
        return $user->hasRole(['Admin', 'SuperAdmin', 'Director General', 'teacher', 'Docente'])
            || $user->isTeacher();
    }

    public function update(User $user, Attendance $attendance): bool
    {
        return $user->hasRole(['Admin', 'SuperAdmin', 'Director General']) ||
               (($user->hasRole(['teacher', 'Docente']) || $user->isTeacher()) && $attendance->teacher_id === optional($user->teacher)->id);
    }

    public function delete(User $user, Attendance $attendance): bool
    {
        return $user->hasRole(['Admin', 'SuperAdmin', 'Director General']) ||
               (($user->hasRole(['teacher', 'Docente']) || $user->isTeacher()) && $attendance->teacher_id === optional($user->teacher)->id);
    }
}
