<?php

declare(strict_types=1);

namespace App\Livewire\Team;

use App\Models\Invite;
use App\Models\InviteStatus;
use App\Models\User;
use App\Services\Invites\InviteService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
final class InviteList extends Component
{
    public string $name = '';

    public string $email = '';

    public function mount(): void
    {
        Gate::authorize('viewAny', Invite::class);
    }

    public function send(InviteService $service): void
    {
        Gate::authorize('create', Invite::class);

        $data = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                function (string $attribute, mixed $value, callable $fail): void {
                    $companyId = auth()->user()->company_id;

                    if (User::query()->where('company_id', $companyId)->where('email', $value)->exists()) {
                        $fail('A user with this email already exists in your company.');

                        return;
                    }

                    $pending = InviteStatus::where('slug', InviteStatus::PENDING)->value('id');

                    if ($pending !== null && Invite::query()
                        ->where('company_id', $companyId)
                        ->where('email', $value)
                        ->where('status_id', $pending)
                        ->exists()
                    ) {
                        $fail('A pending invite already exists for this email.');
                    }
                },
            ],
        ]);

        /** @var User $owner */
        $owner = auth()->user();

        $service->create($owner, $data['name'], $data['email']);

        $this->reset(['name', 'email']);
        $this->dispatch('invite-sent');
    }

    public function resend(int $inviteId, InviteService $service): void
    {
        $invite = Invite::findOrFail($inviteId);
        Gate::authorize('resend', $invite);
        $service->resend($invite);
    }

    public function revoke(int $inviteId, InviteService $service): void
    {
        $invite = Invite::findOrFail($inviteId);
        Gate::authorize('revoke', $invite);
        $service->revoke($invite);
    }

    #[Computed]
    public function invites()
    {
        return Invite::query()
            ->with(['status', 'invitedBy'])
            ->orderByDesc('created_at')
            ->get()
            ->map(function (Invite $invite): Invite {
                if ($invite->status?->slug === InviteStatus::PENDING && $invite->expires_at->isPast()) {
                    app(InviteService::class)->markExpired($invite);
                    $invite->load('status');
                }

                return $invite;
            });
    }

    public function render(): View
    {
        return view('livewire.team.invite-list');
    }
}
