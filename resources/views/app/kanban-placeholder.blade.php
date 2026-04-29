<x-layouts.app page-title="Pipeline">
    <x-ui.page-header title="Pipeline" subtitle="Your sales board.">
        <x-slot:actions>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <x-ui.button type="submit" variant="ghost" data-testid="logout">Logout</x-ui.button>
            </form>
        </x-slot:actions>
    </x-ui.page-header>

    <x-ui.empty-state
        title="Kanban lands in Phase 7"
        description="The Livewire 4 wire:sort board will live here."
    />
</x-layouts.app>
