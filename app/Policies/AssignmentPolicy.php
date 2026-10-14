<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Assignment;

class AssignmentPolicy
{
    private function hasAnySystemRole(User $user, array $acceptedRoles): bool
    {
        $normalize = static fn (string $role): string => strtolower(str_replace(['_', '-'], ' ', trim($role)));
        $accepted = array_map($normalize, $acceptedRoles);

        if (in_array($normalize((string) $user->role), $accepted, true)) {
            return true;
        }

        if (method_exists($user, 'getRoleNames')) {
            foreach ($user->getRoleNames() as $role) {
                if (in_array($normalize((string) $role), $accepted, true)) {
                    return true;
                }
            }
        }

        return false;
    }

    private function canManage(User $user): bool
    {
        return $this->hasAnySystemRole($user, [
            'admin',
            'SuperAdmin',
            'Director General',
            'Director Académico',
            'Director Academico',
        ]);
    }

    public function viewAny(User $user): bool
    {
        return $this->canManage($user)
            || $this->hasAnySystemRole($user, ['Docente', 'teacher']);
    }

    public function view(User $user, Assignment $assignment): bool
    {
        if ($this->hasAnySystemRole($user, ['Docente', 'teacher'])) {
            return $user->teacher_id === $assignment->teacher_id;
        }

        return $this->canManage($user);
    }

    public function create(User $user): bool
    {
        return $this->canManage($user);
    }

    public function update(User $user, Assignment $assignment): bool
    {
        return $this->create($user);
    }

    public function delete(User $user, Assignment $assignment): bool
    {
        return $this->create($user);
    }
}
