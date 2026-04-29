<?php

declare(strict_types=1);

namespace App\Livewire\Invites;

use App\Models\Invite;
use App\Models\InviteStatus;
use App\Models\User;
use App\Services\Invites\InviteService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Livewire\Component;

final class AcceptInvite extends Component
{
    public string $token = '';

    public string $password = '';

    public string $password_confirmation = '';

    public ?Invite $invite = null;

    public ?string $errorMessage = null;

    public function mount(string $token, InviteService $service): void
    {
        $this->token = $token;

        $invite = Invite::query()
            ->withoutGlobalScopes()
            ->with(['status', 'role', 'company'])
            ->where('token', $token)
            ->first();

        if ($invite === null) {
            $this->errorMessage = 'Invalid invitation link.';

            return;
        }

        if ($invite->status?->slug === InviteStatus::REVOKED) {
            $this->errorMessage = 'This invitation has been revoked.';

            return;
        }

        if ($invite->status?->slug === InviteStatus::ACCEPTED) {
            $this->errorMessage = 'This invitation has already been accepted.';

            return;
        }

        if ($service->isExpired($invite)) {
            if ($invite->status?->slug === InviteStatus::PENDING) {
                $service->markExpired($invite);
            }
            $this->errorMessage = 'This invitation has expired.';

            return;
        }

        $this->invite = $invite;
    }

    public function accept(InviteService $service): void
    {
        if ($this->invite === null) {
            return;
        }

        $invite = Invite::query()
            ->withoutGlobalScopes()
            ->where('id', $this->invite->id)
            ->lockForUpdate()
            ->first();

        if ($invite === null || $invite->status?->slug !== InviteStatus::PENDING || $service->isExpired($invite)) {
            $this->errorMessage = 'This invitation is no longer valid.';
            $this->invite = null;

            return;
        }

        $data = $this->validate([
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        $user = DB::transaction(function () use ($invite, $data): User {
            $accepted = InviteStatus::where('slug', InviteStatus::ACCEPTED)->firstOrFail();

            $user = User::create([
                'company_id' => $invite->company_id,
                'role_id' => $invite->role_id,
                'name' => $invite->name,
                'email' => $invite->email,
                'password' => Hash::make($data['password']),
                'email_verified_at' => now(),
                'is_active' => true,
                'must_change_password' => false,
            ]);

            $invite->update([
                'status_id' => $accepted->id,
                'accepted_at' => now(),
                'accepted_user_id' => $user->id,
            ]);

            return $user;
        });

        Auth::login($user);

        $this->redirect(route('kanban'), navigate: true);
    }

    public function render(): View
    {
        return view('livewire.invites.accept');
    }
}
