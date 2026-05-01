<?php

declare(strict_types=1);

namespace App\Livewire\Leads;

use App\Models\Lead;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('components.layouts.app')]
final class LeadList extends Component
{
    #[Url(as: 'q')]
    public string $search = '';

    public function mount(): void
    {
        Gate::authorize('viewAny', Lead::class);
    }

    #[Computed]
    public function leads()
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();

        return Lead::query()
            ->with(['owner:id,name'])
            ->withCount('deals')
            ->when(! $user->isBusinessOwner(), function ($query) use ($user): void {
                $query->where('owner_user_id', $user->id);
            })
            ->when($this->search !== '', function ($query): void {
                $like = '%'.mb_trim($this->search).'%';

                $query->where(function ($inner) use ($like): void {
                    $inner->where('name', 'like', $like)
                        ->orWhere('email', 'like', $like)
                        ->orWhere('phone', 'like', $like)
                        ->orWhereHas('owner', function ($ownerQuery) use ($like): void {
                            $ownerQuery->where('name', 'like', $like);
                        });
                });
            })
            ->orderByDesc('created_at')
            ->get();
    }

    public function render(): View
    {
        return view('livewire.leads.lead-list');
    }
}
