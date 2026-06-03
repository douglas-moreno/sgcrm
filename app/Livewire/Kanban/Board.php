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
    private const DRAWER_TABS = ['overview', 'notes', 'whatsapp'];

    #[Url(as: 'owner')]
    public ?int $ownerFilter = null;

    public ?int $selectedDealId = null;

    public string $activeDrawerTab = 'overview';

    public ?int $pendingLostDealId = null;

    public ?string $pendingLostFromStage = null;

    public string $lossReason = '';

    public function mount(): void
    {
        if ($this->ownerFilter !== null && ! $this->canFilter()) {
            $this->ownerFilter = null;
        }
    }

    public function updateStage(int $dealId, int|string $positionOrStageSlug, ?string $toStageSlug = null): array
    {
        $deal = Deal::query()->findOrFail($dealId);
        Gate::authorize('move', $deal);

        $resolvedStageSlug = is_string($positionOrStageSlug) ? $positionOrStageSlug : $toStageSlug;
        $toStage = PipelineStage::query()->where('slug', $resolvedStageSlug)->firstOrFail();
        $recorder = app(ActivityRecorder::class);

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

    public function openDeal(int $dealId, string $tab = 'overview'): void
    {
        $deal = Deal::query()
            ->with(['lead', 'owner', 'stage'])
            ->findOrFail($dealId);

        Gate::authorize('view', $deal);

        $this->selectedDealId = $deal->id;
        $this->setDrawerTab($tab);
    }

    public function closeDeal(): void
    {
        $this->selectedDealId = null;
        $this->activeDrawerTab = 'overview';
    }

    public function setDrawerTab(string $tab): void
    {
        if (! in_array($tab, self::DRAWER_TABS, true)) {
            throw ValidationException::withMessages(['activeDrawerTab' => 'Invalid tab.']);
        }

        $this->activeDrawerTab = $tab;
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

    #[Computed]
    public function selectedDeal(): ?Deal
    {
        if ($this->selectedDealId === null) {
            return null;
        }

        $deal = Deal::query()
            ->with(['lead', 'owner', 'stage'])
            ->find($this->selectedDealId);

        if ($deal === null) {
            return null;
        }

        Gate::authorize('view', $deal);

        return $deal;
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
