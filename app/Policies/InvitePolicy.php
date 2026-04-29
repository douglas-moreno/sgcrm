<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Invite;
use App\Models\User;

final class InvitePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isBusinessOwner();
    }

    public function view(User $user, Invite $invite): bool
    {
        return $user->isBusinessOwner() && $user->company_id === $invite->company_id;
    }

    public function create(User $user): bool
    {
        return $user->isBusinessOwner();
    }

    public function resend(User $user, Invite $invite): bool
    {
        return $this->view($user, $invite);
    }

    public function revoke(User $user, Invite $invite): bool
    {
        return $this->view($user, $invite);
    }
}
