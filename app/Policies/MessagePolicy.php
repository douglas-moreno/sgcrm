<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Lead;
use App\Models\Message;
use App\Models\User;

final class MessagePolicy
{
    public function viewAny(User $user, Lead $lead): bool
    {
        if ($user->company_id !== $lead->company_id) {
            return false;
        }

        return $user->isBusinessOwner() || $lead->owner_user_id === $user->id;
    }

    public function view(User $user, Message $message): bool
    {
        if ($user->company_id !== $message->company_id) {
            return false;
        }

        return $user->isBusinessOwner() || $message->user_id === $user->id;
    }

    public function create(User $user, Lead $lead): bool
    {
        return $this->viewAny($user, $lead);
    }
}
