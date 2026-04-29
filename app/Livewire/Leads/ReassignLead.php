<?php

declare(strict_types=1);

namespace App\Livewire\Leads;

use App\Models\Lead;
use App\Models\Role;
use App\Models\User;
use App\Services\Leads\ReassignLeadService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Component;

final class ReassignLead extends Component
{
    public Lead $lead;

    public ?int $newOwnerId = null;

    public function mount(Lead $lead): void
    {
        Gate::authorize('reassign', $lead);
        $this->lead = $lead;
    }

    public function reassign(ReassignLeadService $service): void
    {
        Gate::authorize('reassign', $this->lead);

        $data = $this->validate([
            'newOwnerId' => ['required', 'integer', 'exists:users,id'],
        ]);

        $newOwner = User::query()
            ->where('id', $data['newOwnerId'])
            ->where('company_id', $this->lead->company_id)
            ->where('is_active', true)
            ->firstOrFail();

        /** @var User $actor */
        $actor = auth()->user();

        $service->reassign($this->lead, $newOwner, $actor);

        $this->dispatch('lead-reassigned', leadId: $this->lead->id);
        $this->lead = $this->lead->fresh();
        $this->newOwnerId = null;
    }

    #[Computed]
    public function salespeople()
    {
        return User::query()
            ->where('company_id', $this->lead->company_id)
            ->where('is_active', true)
            ->whereHas('role', fn ($q) => $q->where('slug', Role::SALESPERSON))
            ->orderBy('name')
            ->get();
    }

    public function render(): View
    {
        return view('livewire.leads.reassign-lead');
    }
}
