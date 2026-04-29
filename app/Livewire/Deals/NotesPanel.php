<?php

declare(strict_types=1);

namespace App\Livewire\Deals;

use App\Models\ActivityType;
use App\Models\Deal;
use App\Models\DealNote;
use App\Services\Activity\ActivityRecorder;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Component;

final class NotesPanel extends Component
{
    public Deal $deal;

    public string $body = '';

    public function mount(Deal $deal): void
    {
        Gate::authorize('viewAny', [DealNote::class, $deal]);
        $this->deal = $deal;
    }

    public function add(ActivityRecorder $recorder): void
    {
        Gate::authorize('create', [DealNote::class, $this->deal]);

        $data = $this->validate([
            'body' => ['required', 'string', 'max:5000'],
        ]);

        /** @var \App\Models\User $actor */
        $actor = auth()->user();

        DealNote::create([
            'deal_id' => $this->deal->id,
            'user_id' => $actor->id,
            'body' => $data['body'],
        ]);

        $recorder->record(ActivityType::NOTE_ADDED, [
            'company_id' => $this->deal->company_id,
            'user_id' => $actor->id,
            'lead' => $this->deal->lead,
            'deal' => $this->deal,
            'metadata' => ['preview' => mb_substr($data['body'], 0, 80)],
        ]);

        $this->reset('body');
        unset($this->notes);
        $this->dispatch('note-added');
    }

    #[Computed]
    public function notes()
    {
        return DealNote::query()
            ->where('deal_id', $this->deal->id)
            ->with('user')
            ->orderByDesc('created_at')
            ->get();
    }

    public function render(): View
    {
        return view('livewire.deals.notes-panel');
    }
}
