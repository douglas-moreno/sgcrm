<?php

declare(strict_types=1);

use App\Models\Activity;
use App\Models\Company;
use App\Models\Deal;
use App\Models\DealNote;
use App\Models\Invite;
use App\Models\Lead;
use App\Models\Message;
use App\Models\User;
use App\Models\WhatsappConnection;

beforeEach(function (): void {
    $this->seed(Database\Seeders\RoleSeeder::class);
    $this->seed(Database\Seeders\PipelineStageSeeder::class);
    $this->seed(Database\Seeders\InviteStatusSeeder::class);
    $this->seed(Database\Seeders\WhatsappConnectionStatusSeeder::class);
    $this->seed(Database\Seeders\MessageDirectionSeeder::class);
    $this->seed(Database\Seeders\MessageStatusSeeder::class);
    $this->seed(Database\Seeders\MessageTypeSeeder::class);
    $this->seed(Database\Seeders\ActivityTypeSeeder::class);

    $this->company = Company::factory()->create();
    $this->otherCompany = Company::factory()->create();
    $this->owner = User::factory()->businessOwner()->forCompany($this->company)->create();
    $this->seller = User::factory()->salesperson()->forCompany($this->company)->create();
    $this->otherSeller = User::factory()->salesperson()->forCompany($this->company)->create();
    $this->foreignSeller = User::factory()->salesperson()->forCompany($this->otherCompany)->create();
});

it('allows owner to manage users in same company but not deactivate self', function (): void {
    expect($this->owner->can('viewAny', User::class))->toBeTrue()
        ->and($this->seller->can('viewAny', User::class))->toBeFalse()
        ->and($this->owner->can('update', $this->seller))->toBeTrue()
        ->and($this->owner->can('deactivate', $this->seller))->toBeTrue()
        ->and($this->owner->can('deactivate', $this->owner))->toBeFalse()
        ->and($this->owner->can('update', $this->foreignSeller))->toBeFalse();
});

it('scopes lead policy to owner or business owner', function (): void {
    $lead = Lead::factory()->forCompany($this->company)->ownedBy($this->seller)->create();
    $foreignLead = Lead::factory()->forCompany($this->otherCompany)->ownedBy($this->foreignSeller)->create();

    expect($this->seller->can('view', $lead))->toBeTrue()
        ->and($this->otherSeller->can('view', $lead))->toBeFalse()
        ->and($this->owner->can('view', $lead))->toBeTrue()
        ->and($this->seller->can('view', $foreignLead))->toBeFalse()
        ->and($this->seller->can('updateEmail', $lead))->toBeFalse()
        ->and($this->owner->can('updateEmail', $lead))->toBeTrue()
        ->and($this->seller->can('reassign', $lead))->toBeFalse()
        ->and($this->owner->can('reassign', $lead))->toBeTrue();
});

it('scopes deal policy to owner or business owner and locks won deals', function (): void {
    $lead = Lead::factory()->forCompany($this->company)->ownedBy($this->seller)->create();
    $deal = Deal::factory()->forLead($lead)->create();
    $wonDeal = Deal::factory()->forLead($lead)->won()->create();

    expect($this->seller->can('view', $deal))->toBeTrue()
        ->and($this->otherSeller->can('view', $deal))->toBeFalse()
        ->and($this->owner->can('view', $deal))->toBeTrue()
        ->and($this->seller->can('update', $deal))->toBeTrue()
        ->and($this->seller->can('update', $wonDeal))->toBeFalse()
        ->and($this->owner->can('update', $wonDeal))->toBeFalse()
        ->and($this->owner->can('reassign', $deal))->toBeTrue()
        ->and($this->seller->can('reassign', $deal))->toBeFalse();
});

it('scopes deal note policy to deal owner', function (): void {
    $lead = Lead::factory()->forCompany($this->company)->ownedBy($this->seller)->create();
    $deal = Deal::factory()->forLead($lead)->create();
    $note = DealNote::factory()->create([
        'deal_id' => $deal->id,
        'user_id' => $this->seller->id,
    ]);

    expect($this->seller->can('create', [DealNote::class, $deal]))->toBeTrue()
        ->and($this->otherSeller->can('create', [DealNote::class, $deal]))->toBeFalse()
        ->and($this->owner->can('create', [DealNote::class, $deal]))->toBeTrue()
        ->and($this->seller->can('update', $note))->toBeTrue()
        ->and($this->otherSeller->can('update', $note))->toBeFalse()
        ->and($this->owner->can('update', $note))->toBeTrue();
});

it('only allows owner to manage invites in same company', function (): void {
    $invite = Invite::factory()->create([
        'company_id' => $this->company->id,
        'invited_by_user_id' => $this->owner->id,
    ]);
    $foreignInvite = Invite::factory()->create([
        'company_id' => $this->otherCompany->id,
    ]);

    expect($this->owner->can('viewAny', Invite::class))->toBeTrue()
        ->and($this->seller->can('viewAny', Invite::class))->toBeFalse()
        ->and($this->owner->can('create', Invite::class))->toBeTrue()
        ->and($this->seller->can('create', Invite::class))->toBeFalse()
        ->and($this->owner->can('revoke', $invite))->toBeTrue()
        ->and($this->owner->can('revoke', $foreignInvite))->toBeFalse();
});

it('scopes whatsapp connection to its user', function (): void {
    $connection = WhatsappConnection::factory()->create([
        'user_id' => $this->seller->id,
        'company_id' => $this->company->id,
    ]);

    expect($this->seller->can('view', $connection))->toBeTrue()
        ->and($this->seller->can('manage', $connection))->toBeTrue()
        ->and($this->otherSeller->can('manage', $connection))->toBeFalse()
        ->and($this->owner->can('view', $connection))->toBeTrue()
        ->and($this->owner->can('manage', $connection))->toBeFalse();
});

it('scopes message policy to lead owner or business owner', function (): void {
    $lead = Lead::factory()->forCompany($this->company)->ownedBy($this->seller)->create();
    $message = Message::factory()->create([
        'company_id' => $this->company->id,
        'lead_id' => $lead->id,
        'user_id' => $this->seller->id,
    ]);

    expect($this->seller->can('viewAny', [Message::class, $lead]))->toBeTrue()
        ->and($this->otherSeller->can('viewAny', [Message::class, $lead]))->toBeFalse()
        ->and($this->owner->can('viewAny', [Message::class, $lead]))->toBeTrue()
        ->and($this->seller->can('view', $message))->toBeTrue()
        ->and($this->otherSeller->can('view', $message))->toBeFalse();
});

it('makes activities read-only and tenant-scoped', function (): void {
    $lead = Lead::factory()->forCompany($this->company)->ownedBy($this->seller)->create();
    $deal = Deal::factory()->forLead($lead)->create();
    $activity = Activity::factory()->create([
        'company_id' => $this->company->id,
        'user_id' => $this->seller->id,
        'lead_id' => $lead->id,
        'deal_id' => $deal->id,
    ]);

    expect($this->seller->can('view', $activity))->toBeTrue()
        ->and($this->otherSeller->can('view', $activity))->toBeFalse()
        ->and($this->owner->can('view', $activity))->toBeTrue()
        ->and($this->seller->can('create', Activity::class))->toBeFalse()
        ->and($this->seller->can('update', $activity))->toBeFalse()
        ->and($this->seller->can('delete', $activity))->toBeFalse();
});
