<?php

namespace App\Policies;

use App\Models\WhatsappLog;
use App\Models\User;

class WhatsappLogPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(['admin', 'director_general']);
    }

    public function view(User $user, WhatsappLog $log): bool
    {
        return $this->viewAny($user) || $log->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->hasRole(['admin', 'teacher', 'regente', 'director_academico', 'director_general']);
    }

    public function update(User $user, WhatsappLog $log): bool
    {
        return $user->hasRole('admin');
    }

    public function delete(User $user, WhatsappLog $log): bool
    {
        return $user->hasRole('admin');
    }
}
