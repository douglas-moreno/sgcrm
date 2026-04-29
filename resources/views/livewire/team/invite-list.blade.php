<div class="space-y-8" data-testid="invite-list">
    <x-ui.page-header title="Team invites" subtitle="Invite Salespeople via email link." />

    <x-ui.card title="Send invite">
        <form wire:submit="send" class="grid gap-4 sm:grid-cols-2" data-testid="invite-form">
            <x-ui.input wire:model="name" name="name" label="Name" :error="$errors->first('name')" />
            <x-ui.input wire:model="email" name="email" type="email" label="Email" :error="$errors->first('email')" />
            <div class="sm:col-span-2 flex justify-end">
                <x-ui.button type="submit" data-testid="send-invite">Send invite</x-ui.button>
            </div>
        </form>
    </x-ui.card>

    <x-ui.card title="Invites">
        @if ($this->invites->isEmpty())
            <x-ui.empty-state title="No invites yet" description="Send your first invite using the form above." icon="envelope" />
        @else
            <table class="min-w-full text-sm" data-testid="invite-table">
                <thead>
                    <tr class="text-left text-ink-muted">
                        <th class="py-2">Name</th>
                        <th class="py-2">Email</th>
                        <th class="py-2">Status</th>
                        <th class="py-2">Expires</th>
                        <th class="py-2 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($this->invites as $invite)
                        <tr class="border-t border-outline" data-testid="invite-row" data-invite-id="{{ $invite->id }}">
                            <td class="py-2">{{ $invite->name }}</td>
                            <td class="py-2">{{ $invite->email }}</td>
                            <td class="py-2"><x-ui.badge>{{ $invite->status?->name }}</x-ui.badge></td>
                            <td class="py-2">{{ $invite->expires_at->format('M j, Y H:i') }}</td>
                            <td class="py-2 text-right space-x-2">
                                @if ($invite->status?->slug !== \App\Models\InviteStatus::ACCEPTED)
                                    <x-ui.button size="sm" variant="outline" wire:click="resend({{ $invite->id }})" data-testid="resend-{{ $invite->id }}">Resend</x-ui.button>
                                @endif
                                @if ($invite->status?->slug === \App\Models\InviteStatus::PENDING)
                                    <x-ui.button size="sm" variant="danger" wire:click="revoke({{ $invite->id }})" data-testid="revoke-{{ $invite->id }}">Revoke</x-ui.button>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </x-ui.card>
</div>
