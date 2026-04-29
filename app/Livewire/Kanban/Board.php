<?php

declare(strict_types=1);

namespace App\Livewire\Kanban;

use App\Models\ActivityType;
use App\Models\Deal;
use App\Models\PipelineStage;
use App\Models\Role;
use App\Models\User;
use App\Services\Activity\ActivityRecorder;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('components.layouts.app')]
final class Board extends Component
{
    #[Url(as: 'owner')]
    public ?int $ownerFilter = null;

    public ?int $pendingLostDealId = null;

    public ?string $pendingLostFromStage = null;

    public string $lossReason = '';

    public function mount(): void
    {
        if ($this->ownerFilter !== null && ! $this->canFilter()) {
            $this->ownerFilter = null;
        }
    }

    public function updateStage(int $dealId, string $toStageSlug, ActivityRecorder $recorder): array
    {
        $deal = Deal::query()->findOrFail($dealId);
        Gate::authorize('move', $deal);

        $toStage = PipelineStage::query()->where('slug', $toStageSlug)->firstOrFail();

        if ($deal->stage_id === $toStage->id) {
            return ['status' => 'noop'];
        }

        $fromStage = $deal->stage;

        if ($toStage->slug === PipelineStage::LOST) {
            $this->pendingLostDealId = $deal->id;
            $this->pendingLostFromStage = $fromStage?->slug;

            return ['status' => 'requires_loss_reason'];
        }

        $this->applyStageChange($deal, $fromStage, $toStage, $recorder);

        return ['status' => 'ok'];
    }

    public function confirmLoss(ActivityRecorder $recorder): void
    {
        if ($this->pendingLostDealId === null) {
            return;
        }

        $this->validate([
            'lossReason' => ['required', 'string', 'max:1000'],
        ]);

        $deal = Deal::query()->findOrFail($this->pendingLostDealId);
        Gate::authorize('move', $deal);

        $lostStage = PipelineStage::query()->where('slug', PipelineStage::LOST)->firstOrFail();
        $fromStage = $deal->stage;

        $deal->update([
            'stage_id' => $lostStage->id,
            'loss_reason' => $this->lossReason,
            'lost_at' => Carbon::now(),
        ]);

        $recorder->record(ActivityType::STAGE_CHANGED, [
            'company_id' => $deal->company_id,
            'lead' => $deal->lead,
            'deal' => $deal,
            'before' => $fromStage?->slug,
            'after' => $lostStage->slug,
            'metadata' => [
                'from_stage_id' => $fromStage?->id,
                'to_stage_id' => $lostStage->id,
            ],
        ]);

        $recorder->record(ActivityType::DEAL_LOST, [
            'company_id' => $deal->company_id,
            'lead' => $deal->lead,
            'deal' => $deal,
            'metadata' => [
                'loss_reason' => $this->lossReason,
            ],
        ]);

        $this->reset(['pendingLostDealId', 'pendingLostFromStage', 'lossReason']);
    }

    public function cancelLoss(): void
    {
        $this->reset(['pendingLostDealId', 'pendingLostFromStage', 'lossReason']);
    }

    public function setOwnerFilter(?int $ownerId): void
    {
        if (! $this->canFilter()) {
            throw ValidationException::withMessages(['ownerFilter' => 'Forbidden.']);
        }

        $this->ownerFilter = $ownerId;
    }

    public function canFilter(): bool
    {
        return auth()->user()?->isBusinessOwner() === true;
    }

    #[Computed]
    public function stages(): Collection
    {
        return PipelineStage::query()->where('is_active', true)->orderBy('position')->get();
    }

    #[Computed]
    public function dealsByStage(): array
    {
        $query = Deal::query()->with(['lead', 'owner', 'stage']);

        if (! auth()->user()?->isBusinessOwner()) {
            $query->where('owner_user_id', auth()->id());
        } elseif ($this->ownerFilter !== null) {
            $query->where('owner_user_id', $this->ownerFilter);
        }

        $deals = $query->orderByDesc('updated_at')->get();

        return $deals->groupBy('stage_id')->all();
    }

    #[Computed]
    public function salespeople(): Collection
    {
        if (! $this->canFilter()) {
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
        return view('livewire.kanban.board');
    }

    private function applyStageChange(Deal $deal, ?PipelineStage $fromStage, PipelineStage $toStage, ActivityRecorder $recorder): void
    {
        $payload = ['stage_id' => $toStage->id];

        if ($toStage->slug === PipelineStage::WON) {
            $payload['won_at'] = Carbon::now();
        }

        $deal->update($payload);

        $recorder->record(ActivityType::STAGE_CHANGED, [
            'company_id' => $deal->company_id,
            'lead' => $deal->lead,
            'deal' => $deal,
            'before' => $fromStage?->slug,
            'after' => $toStage->slug,
            'metadata' => [
                'from_stage_id' => $fromStage?->id,
                'to_stage_id' => $toStage->id,
            ],
        ]);

        if ($toStage->slug === PipelineStage::WON) {
            $recorder->record(ActivityType::DEAL_WON, [
                'company_id' => $deal->company_id,
                'lead' => $deal->lead,
                'deal' => $deal,
            ]);
        }
    }
}
