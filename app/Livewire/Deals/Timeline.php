<?php

declare(strict_types=1);

namespace App\Livewire\Deals;

use App\Models\Activity;
use App\Models\Deal;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

final class Timeline extends Component
{
    use WithPagination;

    public Deal $deal;

    public int $perPage = 15;

    public function mount(Deal $deal): void
    {
        Gate::authorize('view', $deal);
        $this->deal = $deal;
    }

    #[Computed]
    public function activities()
    {
        return Activity::query()
            ->where('deal_id', $this->deal->id)
            ->with(['type', 'user'])
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate($this->perPage);
    }

    public function render(): View
    {
        return view('livewire.deals.timeline');
    }
}
