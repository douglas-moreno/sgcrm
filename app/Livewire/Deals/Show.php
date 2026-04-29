<?php

declare(strict_types=1);

namespace App\Livewire\Deals;

use App\Models\ActivityType;
use App\Models\Deal;
use App\Models\PipelineStage;
use App\Services\Activity\ActivityRecorder;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
final class Show extends Component
{
    public Deal $deal;

    public string $title = '';

    public string $value = '0';

    public bool $showLostModal = false;

    public string $lossReason = '';

    public function mount(Deal $deal): void
    {
        Gate::authorize('view', $deal);
        $this->hydrateFromDeal($deal);
    }

    public function save(ActivityRecorder $recorder): void
    {
        Gate::authorize('update', $this->deal);

        $data = $this->validate([
            'title' => ['required', 'string', 'max:255'],
            'value' => ['required', 'numeric', 'min:0'],
        ]);

        $original = [
            'title' => (string) $this->deal->title,
            'value' => (string) $this->deal->value,
        ];

        $this->deal->update([
            'title' => $data['title'],
            'value' => $data['value'],
        ]);

        if ($original['title'] !== $data['title']) {
            $recorder->record(ActivityType::DEAL_UPDATED, [
                'company_id' => $this->deal->company_id,
                'lead' => $this->deal->lead,
                'deal' => $this->deal,
                'before' => $original['title'],
                'after' => $data['title'],
                'metadata' => ['field' => 'title'],
            ]);
        }

        if ((float) $original['value'] !== (float) $data['value']) {
            $recorder->record(ActivityType::VALUE_CHANGED, [
                'company_id' => $this->deal->company_id,
                'lead' => $this->deal->lead,
                'deal' => $this->deal,
                'before' => $original['value'],
                'after' => (string) $data['value'],
            ]);
        }

        $this->hydrateFromDeal($this->deal->fresh());
        $this->dispatch('deal-updated', dealId: $this->deal->id);
    }

    public function openLostModal(): void
    {
        Gate::authorize('move', $this->deal);
        $this->showLostModal = true;
        $this->lossReason = '';
    }

    public function cancelLost(): void
    {
        $this->showLostModal = false;
        $this->lossReason = '';
    }

    public function confirmLost(ActivityRecorder $recorder): void
    {
        Gate::authorize('move', $this->deal);

        $this->validate([
            'lossReason' => ['required', 'string', 'max:1000'],
        ]);

        $lostStage = PipelineStage::query()->where('slug', PipelineStage::LOST)->firstOrFail();
        $fromStage = $this->deal->stage;

        $this->deal->update([
            'stage_id' => $lostStage->id,
            'loss_reason' => $this->lossReason,
            'lost_at' => Carbon::now(),
        ]);

        $recorder->record(ActivityType::STAGE_CHANGED, [
            'company_id' => $this->deal->company_id,
            'lead' => $this->deal->lead,
            'deal' => $this->deal,
            'before' => $fromStage?->slug,
            'after' => $lostStage->slug,
            'metadata' => [
                'from_stage_id' => $fromStage?->id,
                'to_stage_id' => $lostStage->id,
            ],
        ]);

        $recorder->record(ActivityType::DEAL_LOST, [
            'company_id' => $this->deal->company_id,
            'lead' => $this->deal->lead,
            'deal' => $this->deal,
            'metadata' => ['loss_reason' => $this->lossReason],
        ]);

        $this->showLostModal = false;
        $this->lossReason = '';
        $this->hydrateFromDeal($this->deal->fresh());
    }

    public function canEdit(): bool
    {
        return auth()->user()?->can('update', $this->deal) ?? false;
    }

    public function render(): View
    {
        return view('livewire.deals.show');
    }

    private function hydrateFromDeal(Deal $deal): void
    {
        $deal->load(['lead', 'owner', 'stage']);
        $this->deal = $deal;
        $this->title = (string) $deal->title;
        $this->value = (string) $deal->value;
    }
}
