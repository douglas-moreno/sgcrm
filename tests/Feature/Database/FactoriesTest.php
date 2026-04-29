<?php

declare(strict_types=1);

use App\Models\Activity;
use App\Models\Company;
use App\Models\Deal;
use App\Models\DealNote;
use App\Models\Invite;
use App\Models\Lead;
use App\Models\Message;
use App\Models\PipelineStage;
use App\Models\Role;
use App\Models\User;
use App\Models\WhatsappConnection;

it('builds persistable rows for every domain factory', function (): void {
    $company = Company::factory()->create();
    $owner = User::factory()->businessOwner()->forCompany($company)->create();
    $sales = User::factory()->salesperson()->forCompany($company)->create();
    $lead = Lead::factory()->forCompany($company)->ownedBy($sales)->create();
    $deal = Deal::factory()->forLead($lead)->create();

    expect($company->exists)->toBeTrue();
    expect($owner->isBusinessOwner())->toBeTrue();
    expect($sales->isSalesperson())->toBeTrue();
    expect($lead->company_id)->toBe($company->id);
    expect($deal->lead_id)->toBe($lead->id);
    expect($deal->owner_user_id)->toBe($sales->id);

    expect(DealNote::factory()->create()->exists)->toBeTrue();
    expect(Activity::factory()->create()->exists)->toBeTrue();
    expect(Invite::factory()->create()->exists)->toBeTrue();
    expect(WhatsappConnection::factory()->create()->exists)->toBeTrue();
    expect(Message::factory()->create()->exists)->toBeTrue();
    expect(Role::factory()->create()->exists)->toBeTrue();
    expect(PipelineStage::factory()->create()->exists)->toBeTrue();
});

it('produces deals in specific stages with loss reason and won states', function (): void {
    $lost = Deal::factory()->withLossReason('Budget cut')->create();
    $won = Deal::factory()->won()->create();

    expect($lost->loss_reason)->toBe('Budget cut');
    expect($lost->lost_at)->not->toBeNull();
    expect($lost->stage->slug)->toBe(PipelineStage::LOST);

    expect($won->won_at)->not->toBeNull();
    expect($won->stage->slug)->toBe(PipelineStage::WON);
});
