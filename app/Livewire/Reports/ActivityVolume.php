<?php

declare(strict_types=1);

namespace App\Livewire\Reports;

use App\Models\User;
use App\Services\Reports\ActivityVolumeReport;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
final class ActivityVolume extends Component
{
    public ?string $from = null;

    public ?string $to = null;

    #[Computed]
    public function totals(): array
    {
        return app(ActivityVolumeReport::class)->totalsByType($this->filters());
    }

    #[Computed]
    public function bySalesperson(): array
    {
        $rows = app(ActivityVolumeReport::class)->bySalesperson($this->filters());
        $userIds = array_column($rows, 'user_id');
        $names = User::query()->whereIn('id', $userIds)->pluck('name', 'id');

        return array_map(static function (array $row) use ($names): array {
            $row['name'] = (string) ($names[$row['user_id']] ?? 'Unknown');

            return $row;
        }, $rows);
    }

    #[Computed]
    public function timeSeries(): array
    {
        return app(ActivityVolumeReport::class)->timeSeries($this->filters());
    }

    /**
     * @return array<string, mixed>
     */
    private function filters(): array
    {
        return [
            'company_id' => auth()->user()->company_id,
            'from' => $this->from,
            'to' => $this->to,
        ];
    }

    public function render(): View
    {
        return view('livewire.reports.activity-volume');
    }
}
