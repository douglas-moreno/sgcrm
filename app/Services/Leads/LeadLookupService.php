<?php

declare(strict_types=1);

namespace App\Services\Leads;

use App\Models\Lead;
use App\Models\User;

final class LeadLookupService
{
    public function findInCompany(User $actor, string $email): ?Lead
    {
        return Lead::query()
            ->withoutGlobalScopes()
            ->where('company_id', $actor->company_id)
            ->whereRaw('LOWER(email) = ?', [mb_strtolower(mb_trim($email))])
            ->with('owner')
            ->first();
    }

    public function isVisibleTo(User $actor, Lead $lead): bool
    {
        if ($actor->company_id !== $lead->company_id) {
            return false;
        }

        return $actor->isBusinessOwner() || $lead->owner_user_id === $actor->id;
    }
}
