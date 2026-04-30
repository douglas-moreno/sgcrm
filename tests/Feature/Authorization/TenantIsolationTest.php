<?php

declare(strict_types=1);

use App\Models\Activity;
use App\Models\Company;
use App\Models\Deal;
use App\Models\Invite;
use App\Models\Lead;
use App\Models\Message;
use App\Models\User;

beforeEach(function (): void {
    $this->seed(Database\Seeders\RoleSeeder::class);
    $this->seed(Database\Seeders\PipelineStageSeeder::class);
    $this->seed(Database\Seeders\InviteStatusSeeder::class);
    $this->seed(Database\Seeders\WhatsappConnectionStatusSeeder::class);
    $this->seed(Database\Seeders\MessageDirectionSeeder::class);
    $this->seed(Database\Seeders\MessageStatusSeeder::class);
    $this->seed(Database\Seeders\MessageTypeSeeder::class);
    $this->seed(Database\Seeders\ActivityTypeSeeder::class);

    $this->companyA = Company::factory()->create();
    $this->companyB = Company::factory()->create();
    $this->ownerA = User::factory()->businessOwner()->forCompany($this->companyA)->create();
    $this->sellerA = User::factory()->salesperson()->forCompany($this->companyA)->create();
    $this->ownerB = User::factory()->businessOwner()->forCompany($this->companyB)->create();
    $this->sellerB = User::factory()->salesperson()->forCompany($this->companyB)->create();
});

it('cannot read leads from another company', function (): void {
    Lead::factory()->forCompany($this->companyA)->ownedBy($this->sellerA)->count(2)->create();
    Lead::factory()->forCompany($this->companyB)->ownedBy($this->sellerB)->count(3)->create();

    $this->actingAs($this->ownerA);
    expect(Lead::count())->toBe(2);

    $this->actingAs($this->ownerB);
    expect(Lead::count())->toBe(3);
});

it('cannot read deals from another company', function (): void {
    $leadA = Lead::factory()->forCompany($this->companyA)->ownedBy($this->sellerA)->create();
    $leadB = Lead::factory()->forCompany($this->companyB)->ownedBy($this->sellerB)->create();
    Deal::factory()->forLead($leadA)->count(2)->create();
    Deal::factory()->forLead($leadB)->count(4)->create();

    $this->actingAs($this->ownerA);
    expect(Deal::count())->toBe(2);

    $this->actingAs($this->ownerB);
    expect(Deal::count())->toBe(4);
});

it('cannot read messages from another company', function (): void {
    $leadA = Lead::factory()->forCompany($this->companyA)->ownedBy($this->sellerA)->create();
    $leadB = Lead::factory()->forCompany($this->companyB)->ownedBy($this->sellerB)->create();
    Message::factory()->count(2)->create([
        'company_id' => $this->companyA->id,
        'lead_id' => $leadA->id,
        'user_id' => $this->sellerA->id,
    ]);
    Message::factory()->count(3)->create([
        'company_id' => $this->companyB->id,
        'lead_id' => $leadB->id,
        'user_id' => $this->sellerB->id,
    ]);

    $this->actingAs($this->ownerA);
    expect(Message::count())->toBe(2);

    $this->actingAs($this->ownerB);
    expect(Message::count())->toBe(3);
});

it('cannot read invites from another company', function (): void {
    Invite::factory()->count(2)->create([
        'company_id' => $this->companyA->id,
        'invited_by_user_id' => $this->ownerA->id,
    ]);
    Invite::factory()->count(1)->create([
        'company_id' => $this->companyB->id,
        'invited_by_user_id' => $this->ownerB->id,
    ]);

    $this->actingAs($this->ownerA);
    expect(Invite::count())->toBe(2);

    $this->actingAs($this->ownerB);
    expect(Invite::count())->toBe(1);
});

it('cannot read activities from another company', function (): void {
    $leadA = Lead::factory()->forCompany($this->companyA)->ownedBy($this->sellerA)->create();
    $dealA = Deal::factory()->forLead($leadA)->create();
    $leadB = Lead::factory()->forCompany($this->companyB)->ownedBy($this->sellerB)->create();
    $dealB = Deal::factory()->forLead($leadB)->create();

    Activity::factory()->count(3)->create([
        'company_id' => $this->companyA->id,
        'user_id' => $this->sellerA->id,
        'lead_id' => $leadA->id,
        'deal_id' => $dealA->id,
    ]);
    Activity::factory()->count(2)->create([
        'company_id' => $this->companyB->id,
        'user_id' => $this->sellerB->id,
        'lead_id' => $leadB->id,
        'deal_id' => $dealB->id,
    ]);

    $this->actingAs($this->ownerA);
    expect(Activity::count())->toBe(3);

    $this->actingAs($this->ownerB);
    expect(Activity::count())->toBe(2);
});

it('cannot read users from another company via UserPolicy', function (): void {
    expect($this->ownerA->can('view', $this->sellerB))->toBeFalse()
        ->and($this->ownerA->can('update', $this->sellerB))->toBeFalse()
        ->and($this->ownerB->can('view', $this->sellerA))->toBeFalse();
});

it('cannot write a lead pointing to another company via auto-fill', function (): void {
    $this->actingAs($this->sellerA);

    $lead = Lead::create([
        'owner_user_id' => $this->sellerA->id,
        'name' => 'Auto',
        'email' => 'auto@example.com',
    ]);

    expect($lead->company_id)->toBe($this->companyA->id);
});

it('allows the same lead email across different companies', function (): void {
    Lead::factory()->forCompany($this->companyA)->ownedBy($this->sellerA)->create(['email' => 'shared@x.com']);
    Lead::factory()->forCompany($this->companyB)->ownedBy($this->sellerB)->create(['email' => 'shared@x.com']);

    $this->actingAs($this->ownerA);
    expect(Lead::where('email', 'shared@x.com')->count())->toBe(1);

    $this->actingAs($this->ownerB);
    expect(Lead::where('email', 'shared@x.com')->count())->toBe(1);
});

it('rejects duplicate lead email within the same company', function (): void {
    Lead::factory()->forCompany($this->companyA)->ownedBy($this->sellerA)->create(['email' => 'dup@x.com']);

    expect(fn () => Lead::factory()->forCompany($this->companyA)->ownedBy($this->sellerA)->create(['email' => 'dup@x.com']))
        ->toThrow(Illuminate\Database\QueryException::class);
});

it('cannot read whatsapp connections from another company', function (): void {
    App\Models\WhatsappConnection::factory()->create([
        'user_id' => $this->sellerA->id,
        'company_id' => $this->companyA->id,
    ]);
    App\Models\WhatsappConnection::factory()->create([
        'user_id' => $this->sellerB->id,
        'company_id' => $this->companyB->id,
    ]);

    $this->actingAs($this->ownerA);
    expect(App\Models\WhatsappConnection::count())->toBe(1);

    $this->actingAs($this->ownerB);
    expect(App\Models\WhatsappConnection::count())->toBe(1);
});
