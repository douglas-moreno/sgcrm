<div class="space-y-4" data-testid="lead-list">
    <x-ui.page-header title="Leads" subtitle=" Navegue e gerencie os leads para seu pipeline.">
        <x-slot:actions>
            <livewire:leads.create-lead @lead-created="$refresh" />
        </x-slot:actions>
    </x-ui.page-header>

    <div class="flex items-center gap-3">
        <x-ui.input
            wire:model.live.debounce.300ms="search"
            name="search"
            placeholder="Pesquisar por nome, email, telefone ou vendedor..."
            data-testid="lead-search"
        />
    </div>

    <x-ui.card>
        @if ($this->leads->isEmpty())
            <x-ui.empty-state title="Nenhum lead encontrado" description="Tente uma pesquisa diferente ou adicione um novo lead." icon="users" />
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm" data-testid="lead-table">
                    <thead>
                        <tr class="text-left text-ink-muted">
                            <th class="py-2">Nome</th>
                            <th class="py-2">Email</th>
                            <th class="py-2">Celular</th>
                            <th class="py-2">Vendedor</th>
                            <th class="py-2">Deals</th>
                            <th class="py-2">Criado</th>
                            <th class="py-2 text-right">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($this->leads as $lead)
                            <tr class="border-t border-outline" data-testid="lead-row" data-lead-id="{{ $lead->id }}" wire:key="lead-{{ $lead->id }}">
                                <td class="py-2 font-medium text-ink">{{ $lead->name }}</td>
                                <td class="py-2">{{ $lead->email }}</td>
                                <td class="py-2">{{ $lead->phone ?: 'N/A' }}</td>
                                <td class="py-2">
                                    <x-ui.badge>{{ $lead->owner?->name ?? 'Não Atribuído' }}</x-ui.badge>
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
                                            <x-ui.button type="button" size="sm" variant="outline">Editar</x-ui.button>
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
