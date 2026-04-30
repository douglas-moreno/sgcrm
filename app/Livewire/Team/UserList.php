<?php

declare(strict_types=1);

namespace App\Livewire\Team;

use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('components.layouts.app')]
final class UserList extends Component
{
    #[Url(as: 'q')]
    public string $search = '';

    public function mount(): void
    {
        Gate::authorize('viewAny', User::class);
    }

    public function deactivate(int $userId): void
    {
        $target = User::query()->withoutGlobalScopes()->findOrFail($userId);
        Gate::authorize('deactivate', $target);
        $target->update(['is_active' => false]);
    }

    public function reactivate(int $userId): void
    {
        $target = User::query()->withoutGlobalScopes()->findOrFail($userId);
        Gate::authorize('reactivate', $target);
        $target->update(['is_active' => true]);
    }

    #[Computed]
    public function users()
    {
        return User::query()
            ->where('company_id', auth()->user()->company_id)
            ->with('role')
            ->when($this->search !== '', function ($q): void {
                $like = '%'.mb_trim($this->search).'%';
                $q->where(function ($inner) use ($like): void {
                    $inner->where('name', 'like', $like)
                        ->orWhere('email', 'like', $like);
                });
            })
            ->orderBy('name')
            ->get();
    }

    public function render(): View
    {
        return view('livewire.team.user-list');
    }
}
