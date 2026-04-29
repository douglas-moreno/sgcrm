<?php

declare(strict_types=1);

namespace App\Livewire\Whatsapp;

use App\Models\WhatsappConnection;
use App\Services\Whatsapp\ConnectionService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Component;

final class ConnectionPanel extends Component
{
    public function connect(ConnectionService $service): void
    {
        Gate::authorize('create', WhatsappConnection::class);

        /** @var \App\Models\User $user */
        $user = auth()->user();
        $service->connect($user);
        $this->dispatch('whatsapp-connected');
    }

    public function refreshQr(ConnectionService $service): void
    {
        $connection = $this->connection;

        if ($connection === null) {
            return;
        }

        Gate::authorize('manage', $connection);
        $service->refreshQr($connection);
    }

    public function disconnect(ConnectionService $service): void
    {
        $connection = $this->connection;

        if ($connection === null) {
            return;
        }

        Gate::authorize('manage', $connection);
        $service->disconnect($connection);
        $this->dispatch('whatsapp-disconnected');
    }

    #[Computed]
    public function connection(): ?WhatsappConnection
    {
        return WhatsappConnection::query()
            ->where('user_id', auth()->id())
            ->with('status')
            ->first();
    }

    public function render(): View
    {
        return view('livewire.whatsapp.connection-panel');
    }
}
