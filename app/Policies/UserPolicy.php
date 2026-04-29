<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;

final class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isBusinessOwner();
    }

    public function view(User $user, User $target): bool
    {
        return $user->company_id === $target->company_id
            && ($user->isBusinessOwner() || $user->id === $target->id);
    }

    public function create(User $user): bool
    {
        return $user->isBusinessOwner();
    }

    public function update(User $user, User $target): bool
    {
        return $user->isBusinessOwner() && $user->company_id === $target->company_id;
    }

    public function deactivate(User $user, User $target): bool
    {
        return $user->isBusinessOwner()
            && $user->company_id === $target->company_id
            && $user->id !== $target->id;
    }

    public function reactivate(User $user, User $target): bool
    {
        return $this->deactivate($user, $target);
    }
}
