<div class="space-y-4" data-testid="lead-list">
    <x-ui.page-header title="Leads" subtitle="Browse and manage leads for your pipeline.">
        <x-slot:actions>
            <livewire:leads.create-lead @lead-created="$refresh" />
        </x-slot:actions>
    </x-ui.page-header>

    <div class="flex items-center gap-3">
        <x-ui.input
            wire:model.live.debounce.300ms="search"
            name="search"
            placeholder="Search by name, email, phone, or owner"
            data-testid="lead-search"
        />
    </div>

    <x-ui.card>
        @if ($this->leads->isEmpty())
            <x-ui.empty-state title="No leads found" description="Try a different search or add a new lead." icon="users" />
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm" data-testid="lead-table">
                    <thead>
                        <tr class="text-left text-ink-muted">
                            <th class="py-2">Name</th>
                            <th class="py-2">Email</th>
                            <th class="py-2">Phone</th>
                            <th class="py-2">Owner</th>
                            <th class="py-2">Deals</th>
                            <th class="py-2">Created</th>
                            <th class="py-2 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($this->leads as $lead)
                            <tr class="border-t border-outline" data-testid="lead-row" data-lead-id="{{ $lead->id }}" wire:key="lead-{{ $lead->id }}">
                                <td class="py-2 font-medium text-ink">{{ $lead->name }}</td>
                                <td class="py-2">{{ $lead->email }}</td>
                                <td class="py-2">{{ $lead->phone ?: 'N/A' }}</td>
                                <td class="py-2">
                                    <x-ui.badge>{{ $lead->owner?->name ?? 'Unassigned' }}</x-ui.badge>
                                </td>
                                <td class="py-2">{{ $lead->deals_count }}</td>
                                <td class="py-2">{{ $lead->created_at->format('M j, Y') }}</td>
                                <td class="py-2">
                                    <div class="flex justify-end gap-2">
                                        @if ($lead->phone)
                                            <a href="{{ route('whatsapp.conversation', $lead) }}" data-testid="lead-chat-{{ $lead->id }}">
                                                <x-ui.button type="button" size="sm" variant="ghost">WhatsApp</x-ui.button>
                                            </a>
                                        @endif

                                        <a href="{{ route('leads.edit', $lead) }}" data-testid="lead-edit-{{ $lead->id }}">
                                            <x-ui.button type="button" size="sm" variant="outline">Open</x-ui.button>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-ui.card>
</div>
