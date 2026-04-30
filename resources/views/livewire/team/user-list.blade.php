<div class="space-y-4" data-testid="user-list">
    <x-ui.page-header title="Team" subtitle="Manage Salespeople in your company.">
        <x-slot:actions>
            <a href="{{ route('team.invites') }}" data-testid="invite-link">
                <x-ui.button type="button" variant="primary">Invite Salesperson</x-ui.button>
            </a>
        </x-slot:actions>
    </x-ui.page-header>

    <div class="flex items-center gap-3">
        <x-ui.input wire:model.live.debounce.300ms="search" name="search" placeholder="Search by name or email" data-testid="user-search" />
    </div>

    <x-ui.card>
        @if ($this->users->isEmpty())
            <x-ui.empty-state title="No users found" description="Try a different search or invite a Salesperson." icon="users" />
        @else
            <table class="min-w-full text-sm" data-testid="user-table">
                <thead>
                    <tr class="text-left text-ink-muted">
                        <th class="py-2">Name</th>
                        <th class="py-2">Email</th>
                        <th class="py-2">Role</th>
                        <th class="py-2">Status</th>
                        <th class="py-2">Joined</th>
                        <th class="py-2 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($this->users as $user)
                        <tr class="border-t border-outline" data-testid="user-row" data-user-id="{{ $user->id }}">
                            <td class="py-2">{{ $user->name }}</td>
                            <td class="py-2">{{ $user->email }}</td>
                            <td class="py-2"><x-ui.badge>{{ $user->role?->name }}</x-ui.badge></td>
                            <td class="py-2">
                                @if ($user->is_active)
                                    <x-ui.badge variant="success">Active</x-ui.badge>
                                @else
                                    <x-ui.badge variant="danger">Inactive</x-ui.badge>
                                @endif
                            </td>
                            <td class="py-2">{{ $user->created_at->format('M j, Y') }}</td>
                            <td class="py-2 text-right space-x-2">
                                @if ($user->id !== auth()->id())
                                    @if ($user->is_active)
                                        <x-ui.button size="sm" variant="danger" wire:click="deactivate({{ $user->id }})" data-testid="deactivate-{{ $user->id }}">Deactivate</x-ui.button>
                                    @else
                                        <x-ui.button size="sm" variant="success" wire:click="reactivate({{ $user->id }})" data-testid="reactivate-{{ $user->id }}">Reactivate</x-ui.button>
                                    @endif
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </x-ui.card>
</div>
