<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use App\Models\WhatsappConnection;

final class WhatsappConnectionPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, WhatsappConnection $connection): bool
    {
        if ($user->company_id !== $connection->company_id) {
            return false;
        }

        return $user->isBusinessOwner() || $connection->user_id === $user->id;
    }

    public function manage(User $user, WhatsappConnection $connection): bool
    {
        return $user->company_id === $connection->company_id
            && $connection->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return true;
    }
}
