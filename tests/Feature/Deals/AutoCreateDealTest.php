<?php

declare(strict_types=1);

use App\Models\Company;
use App\Models\Deal;
use App\Models\Lead;
use App\Models\PipelineStage;
use App\Models\User;
use App\Services\Leads\CreateLeadWithDeal;

beforeEach(function (): void {
    $this->seed(Database\Seeders\RoleSeeder::class);
    $this->seed(Database\Seeders\PipelineStageSeeder::class);
    $this->seed(Database\Seeders\ActivityTypeSeeder::class);

    $this->company = Company::factory()->create();
    $this->owner = User::factory()->businessOwner()->forCompany($this->company)->create();
    $this->seller = User::factory()->salesperson()->forCompany($this->company)->create();
});

it('creates lead and deal in one transaction with new_lead stage', function (): void {
    $service = app(CreateLeadWithDeal::class);

    $result = $service->create($this->owner, $this->seller, [
        'name' => 'Acme',
        'email' => 'auto@acme.test',
    ]);

    $stageId = PipelineStage::where('slug', PipelineStage::NEW_LEAD)->value('id');

    expect($result['lead']->email)->toBe('auto@acme.test')
        ->and($result['deal']->stage_id)->toBe($stageId)
        ->and($result['deal']->title)->toBe('Acme')
        ->and((float) $result['deal']->value)->toBe(0.0);
});

it('rolls back lead and deal when stage missing', function (): void {
    PipelineStage::query()->where('slug', PipelineStage::NEW_LEAD)->delete();

    $service = app(CreateLeadWithDeal::class);

    expect(fn () => $service->create($this->owner, $this->seller, [
        'name' => 'Will Fail',
        'email' => 'rollback@acme.test',
    ]))->toThrow(Illuminate\Database\Eloquent\ModelNotFoundException::class);

    expect(Lead::query()->withoutGlobalScopes()->where('email', 'rollback@acme.test')->exists())->toBeFalse()
        ->and(Deal::query()->withoutGlobalScopes()->where('title', 'Will Fail')->exists())->toBeFalse();
});

it('rejects owner from another company', function (): void {
    $other = Company::factory()->create();
    $foreign = User::factory()->salesperson()->forCompany($other)->create();

    $service = app(CreateLeadWithDeal::class);

    expect(fn () => $service->create($this->owner, $foreign, [
        'name' => 'Bad',
        'email' => 'bad@acme.test',
    ]))->toThrow(InvalidArgumentException::class);
});
