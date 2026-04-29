<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Lead;
use App\Models\User;

final class LeadPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Lead $lead): bool
    {
        if ($user->company_id !== $lead->company_id) {
            return false;
        }

        return $user->isBusinessOwner() || $lead->owner_user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Lead $lead): bool
    {
        return $this->view($user, $lead);
    }

    public function updateEmail(User $user, Lead $lead): bool
    {
        return $user->isBusinessOwner() && $user->company_id === $lead->company_id;
    }

    public function reassign(User $user, Lead $lead): bool
    {
        return $user->isBusinessOwner() && $user->company_id === $lead->company_id;
    }

    public function delete(User $user, Lead $lead): bool
    {
        return $user->isBusinessOwner() && $user->company_id === $lead->company_id;
    }
}
