<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Deal;
use App\Models\User;

final class DealPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Deal $deal): bool
    {
        if ($user->company_id !== $deal->company_id) {
            return false;
        }

        return $user->isBusinessOwner() || $deal->owner_user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Deal $deal): bool
    {
        if (! $this->view($user, $deal)) {
            return false;
        }

        return $deal->won_at === null;
    }

    public function move(User $user, Deal $deal): bool
    {
        return $this->update($user, $deal);
    }

    public function reassign(User $user, Deal $deal): bool
    {
        return $user->isBusinessOwner() && $user->company_id === $deal->company_id;
    }

    public function delete(User $user, Deal $deal): bool
    {
        return $user->isBusinessOwner() && $user->company_id === $deal->company_id;
    }
}
