<?php

declare(strict_types=1);

namespace App\Livewire\Deals;

use App\Models\Lead;
use App\Services\Leads\CreateLeadWithDeal;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

final class AddDeal extends Component
{
    public Lead $lead;

    public string $title = '';

    public string $value = '0';

    public function mount(Lead $lead): void
    {
        Gate::authorize('view', $lead);
        $this->lead = $lead;
    }

    public function save(CreateLeadWithDeal $service): void
    {
        Gate::authorize('view', $this->lead);

        $data = $this->validate([
            'title' => ['required', 'string', 'max:255'],
            'value' => ['required', 'numeric', 'min:0'],
        ]);

        /** @var \App\Models\User $actor */
        $actor = auth()->user();

        $deal = $service->addDealForLead(
            $actor,
            $this->lead,
            $data['title'],
            (float) $data['value'],
        );

        $this->reset(['title']);
        $this->value = '0';
        $this->dispatch('deal-added', dealId: $deal->id);
    }

    public function render(): View
    {
        return view('livewire.deals.add-deal');
    }
}
