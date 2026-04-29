<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Deal;
use App\Models\DealNote;
use App\Models\User;

final class DealNotePolicy
{
    public function viewAny(User $user, Deal $deal): bool
    {
        if ($user->company_id !== $deal->company_id) {
            return false;
        }

        return $user->isBusinessOwner() || $deal->owner_user_id === $user->id;
    }

    public function create(User $user, Deal $deal): bool
    {
        return $this->viewAny($user, $deal);
    }

    public function update(User $user, DealNote $note): bool
    {
        return $user->isBusinessOwner() || $note->user_id === $user->id;
    }

    public function delete(User $user, DealNote $note): bool
    {
        return $user->isBusinessOwner() || $note->user_id === $user->id;
    }
}
