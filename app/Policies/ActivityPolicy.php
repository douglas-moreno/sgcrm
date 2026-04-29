<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Activity;
use App\Models\User;

final class ActivityPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Activity $activity): bool
    {
        if ($user->company_id !== $activity->company_id) {
            return false;
        }

        if ($user->isBusinessOwner()) {
            return true;
        }

        if ($activity->deal_id !== null && $activity->deal !== null) {
            return $activity->deal->owner_user_id === $user->id;
        }

        if ($activity->lead_id !== null && $activity->lead !== null) {
            return $activity->lead->owner_user_id === $user->id;
        }

        return $activity->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, Activity $activity): bool
    {
        return false;
    }

    public function delete(User $user, Activity $activity): bool
    {
        return false;
    }
}
