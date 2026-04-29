<?php

declare(strict_types=1);

namespace App\Livewire\Leads;

use App\Models\Lead;
use App\Models\Role;
use App\Models\User;
use App\Services\Leads\CreateLeadWithDeal;
use App\Services\Leads\LeadLookupService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Component;

final class CreateLead extends Component
{
    public bool $open = false;

    public string $name = '';

    public string $email = '';

    public string $phone = '';

    public string $notes = '';

    public ?int $ownerUserId = null;

    public ?int $matchedLeadId = null;

    public bool $matchVisible = false;

    public ?string $matchOwnerName = null;

    public ?string $matchLeadName = null;

    public function mount(): void
    {
        Gate::authorize('create', Lead::class);
        $this->ownerUserId = auth()->id();
    }

    public function openModal(): void
    {
        $this->resetExcept('open');
        $this->ownerUserId = auth()->id();
        $this->open = true;
    }

    public function closeModal(): void
    {
        $this->open = false;
        $this->resetExcept('open');
    }

    public function updatedEmail(LeadLookupService $lookup): void
    {
        $this->matchedLeadId = null;
        $this->matchVisible = false;
        $this->matchOwnerName = null;
        $this->matchLeadName = null;

        if (! filter_var($this->email, FILTER_VALIDATE_EMAIL)) {
            return;
        }

        /** @var User $actor */
        $actor = auth()->user();
        $existing = $lookup->findInCompany($actor, $this->email);

        if ($existing === null) {
            return;
        }

        $this->matchedLeadId = $existing->id;
        $this->matchLeadName = $existing->name;
        $this->matchOwnerName = $existing->owner?->name;
        $this->matchVisible = $lookup->isVisibleTo($actor, $existing);
    }

    public function save(CreateLeadWithDeal $service): void
    {
        Gate::authorize('create', Lead::class);

        /** @var User $actor */
        $actor = auth()->user();

        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                function (string $attribute, mixed $value, callable $fail) use ($actor): void {
                    $exists = Lead::query()
                        ->withoutGlobalScopes()
                        ->where('company_id', $actor->company_id)
                        ->whereRaw('LOWER(email) = ?', [mb_strtolower(mb_trim((string) $value))])
                        ->exists();

                    if ($exists) {
                        $fail('A lead with this email already exists in your company.');
                    }
                },
            ],
            'phone' => ['nullable', 'string', 'max:50'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];

        if ($actor->isBusinessOwner()) {
            $rules['ownerUserId'] = [
                'required',
                'integer',
                function (string $attribute, mixed $value, callable $fail) use ($actor): void {
                    $valid = User::query()
                        ->where('id', $value)
                        ->where('company_id', $actor->company_id)
                        ->where('is_active', true)
                        ->exists();

                    if (! $valid) {
                        $fail('Invalid owner.');
                    }
                },
            ];
        } else {
            $this->ownerUserId = $actor->id;
        }

        $data = $this->validate($rules);

        $owner = User::query()->findOrFail($this->ownerUserId);

        $service->create(
            $actor,
            $owner,
            [
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'notes' => $data['notes'] ?? null,
            ],
        );

        $this->dispatch('lead-created');
        $this->closeModal();
    }

    public function reuseExisting(CreateLeadWithDeal $service, LeadLookupService $lookup): void
    {
        if ($this->matchedLeadId === null) {
            return;
        }

        /** @var User $actor */
        $actor = auth()->user();

        $lead = Lead::query()->withoutGlobalScopes()->findOrFail($this->matchedLeadId);

        if (! $lookup->isVisibleTo($actor, $lead)) {
            $this->addError('email', 'Lead exists; contact your manager.');

            return;
        }

        $this->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $service->addDealForLead($actor, $lead, $this->name);

        $this->dispatch('lead-reused', leadId: $lead->id);
        $this->closeModal();
    }

    #[Computed]
    public function salespeople()
    {
        if (! auth()->user()?->isBusinessOwner()) {
            return collect();
        }

        return User::query()
            ->where('company_id', auth()->user()->company_id)
            ->where('is_active', true)
            ->whereHas('role', fn ($q) => $q->where('slug', Role::SALESPERSON))
            ->orderBy('name')
            ->get();
    }

    public function render(): View
    {
        return view('livewire.leads.create-lead');
    }
}
