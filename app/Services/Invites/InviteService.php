<?php

declare(strict_types=1);

namespace App\Services\Invites;

use App\Jobs\SendInviteJob;
use App\Models\Invite;
use App\Models\InviteStatus;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

final class InviteService
{
    public const TOKEN_BYTES = 48;

    public const EXPIRY_DAYS = 7;

    public function create(User $owner, string $name, string $email): Invite
    {
        $role = Role::where('slug', Role::SALESPERSON)->firstOrFail();
        $pending = InviteStatus::where('slug', InviteStatus::PENDING)->firstOrFail();

        $invite = Invite::create([
            'company_id' => $owner->company_id,
            'invited_by_user_id' => $owner->id,
            'role_id' => $role->id,
            'status_id' => $pending->id,
            'name' => $name,
            'email' => $email,
            'token' => $this->generateToken(),
            'expires_at' => Carbon::now()->addDays(self::EXPIRY_DAYS),
        ]);

        SendInviteJob::dispatch($invite);

        return $invite;
    }

    public function resend(Invite $invite): Invite
    {
        $pending = InviteStatus::where('slug', InviteStatus::PENDING)->firstOrFail();

        $invite->update([
            'token' => $this->generateToken(),
            'expires_at' => Carbon::now()->addDays(self::EXPIRY_DAYS),
            'status_id' => $pending->id,
            'accepted_at' => null,
            'accepted_user_id' => null,
        ]);

        SendInviteJob::dispatch($invite->fresh());

        return $invite;
    }

    public function revoke(Invite $invite): Invite
    {
        $revoked = InviteStatus::where('slug', InviteStatus::REVOKED)->firstOrFail();

        $invite->update([
            'status_id' => $revoked->id,
        ]);

        return $invite;
    }

    public function markExpired(Invite $invite): Invite
    {
        $expired = InviteStatus::where('slug', InviteStatus::EXPIRED)->firstOrFail();
        $invite->update(['status_id' => $expired->id]);

        return $invite;
    }

    public function isPending(Invite $invite): bool
    {
        return $invite->status?->slug === InviteStatus::PENDING;
    }

    public function isExpired(Invite $invite): bool
    {
        return $invite->expires_at->isPast();
    }

    private function generateToken(): string
    {
        return Str::random(self::TOKEN_BYTES);
    }
}
