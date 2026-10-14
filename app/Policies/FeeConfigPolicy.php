<?php

namespace App\Policies;

use App\Models\FeeConfig;
use App\Models\User;

class FeeConfigPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(['admin', 'finanzas', 'director_general']);
    }

    public function view(User $user, FeeConfig $feeConfig): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->hasRole(['admin', 'finanzas']);
    }

    public function update(User $user, FeeConfig $feeConfig): bool
    {
        return $this->create($user);
    }

    public function delete(User $user, FeeConfig $feeConfig): bool
    {
        return $user->hasRole('admin');
    }
}
