<?php

declare(strict_types=1);

namespace App\Livewire\Leads;

use App\Models\ActivityType;
use App\Models\Lead;
use App\Models\User;
use App\Services\Activity\ActivityRecorder;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
final class EditLead extends Component
{
    public Lead $lead;

    public string $name = '';

    public string $email = '';

    public string $phone = '';

    public string $notes = '';

    public function mount(Lead $lead): void
    {
        Gate::authorize('view', $lead);

        $this->lead = $lead;
        $this->name = (string) $lead->name;
        $this->email = (string) $lead->email;
        $this->phone = (string) ($lead->phone ?? '');
        $this->notes = (string) ($lead->notes ?? '');
    }

    public function save(ActivityRecorder $recorder): void
    {
        Gate::authorize('update', $this->lead);

        /** @var User $actor */
        $actor = auth()->user();
        $canEditEmail = $actor->can('updateEmail', $this->lead);

        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];

        if ($canEditEmail) {
            $rules['email'] = [
                'required',
                'email',
                'max:255',
                function (string $attribute, mixed $value, callable $fail): void {
                    $exists = Lead::query()
                        ->withoutGlobalScopes()
                        ->where('company_id', $this->lead->company_id)
                        ->where('id', '!=', $this->lead->id)
                        ->whereRaw('LOWER(email) = ?', [mb_strtolower(mb_trim((string) $value))])
                        ->exists();

                    if ($exists) {
                        $fail('A lead with this email already exists in your company.');
                    }
                },
            ];
        } else {
            $this->email = (string) $this->lead->email;
        }

        $this->validate($rules);

        $original = [
            'name' => (string) $this->lead->name,
            'email' => (string) $this->lead->email,
            'phone' => (string) ($this->lead->phone ?? ''),
            'notes' => (string) ($this->lead->notes ?? ''),
        ];

        $payload = [
            'name' => $this->name,
            'phone' => $this->phone === '' ? null : $this->phone,
            'notes' => $this->notes === '' ? null : $this->notes,
        ];

        if ($canEditEmail) {
            $payload['email'] = $this->email;
        }

        DB::transaction(function () use ($payload, $original, $recorder, $actor): void {
            $this->lead->update($payload);

            foreach ($payload as $field => $newValue) {
                $oldValue = $original[$field];
                $newValueString = (string) ($newValue ?? '');

                if ($oldValue === $newValueString) {
                    continue;
                }

                $recorder->record(ActivityType::LEAD_UPDATED, [
                    'company_id' => $this->lead->company_id,
                    'user_id' => $actor->id,
                    'lead' => $this->lead,
                    'before' => $oldValue,
                    'after' => $newValueString,
                    'metadata' => ['field' => $field],
                ]);
            }
        });

        $this->lead = $this->lead->fresh();
        $this->dispatch('lead-updated', leadId: $this->lead->id);
    }

    public function render(): View
    {
        return view('livewire.leads.edit-lead');
    }
}
