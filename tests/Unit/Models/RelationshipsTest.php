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
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

uses(Tests\TestCase::class);

it('declares Company relationships', function (): void {
    $company = new Company;

    expect($company->users())->toBeInstanceOf(HasMany::class);
    expect($company->leads())->toBeInstanceOf(HasMany::class);
    expect($company->deals())->toBeInstanceOf(HasMany::class);
    expect($company->invites())->toBeInstanceOf(HasMany::class);
    expect($company->whatsappConnections())->toBeInstanceOf(HasMany::class);
});

it('declares User relationships', function (): void {
    $user = new User;

    expect($user->company())->toBeInstanceOf(BelongsTo::class);
    expect($user->role())->toBeInstanceOf(BelongsTo::class);
    expect($user->ownedLeads())->toBeInstanceOf(HasMany::class);
    expect($user->ownedDeals())->toBeInstanceOf(HasMany::class);
    expect($user->whatsappConnection())->toBeInstanceOf(HasOne::class);
});

it('declares Lead relationships', function (): void {
    $lead = new Lead;

    expect($lead->company())->toBeInstanceOf(BelongsTo::class);
    expect($lead->owner())->toBeInstanceOf(BelongsTo::class);
    expect($lead->deals())->toBeInstanceOf(HasMany::class);
    expect($lead->messages())->toBeInstanceOf(HasMany::class);
});

it('declares Deal relationships', function (): void {
    $deal = new Deal;

    expect($deal->company())->toBeInstanceOf(BelongsTo::class);
    expect($deal->lead())->toBeInstanceOf(BelongsTo::class);
    expect($deal->owner())->toBeInstanceOf(BelongsTo::class);
    expect($deal->stage())->toBeInstanceOf(BelongsTo::class);
    expect($deal->notes())->toBeInstanceOf(HasMany::class);
    expect($deal->messages())->toBeInstanceOf(HasMany::class);
});

it('declares supporting model relationships', function (): void {
    expect((new DealNote)->deal())->toBeInstanceOf(BelongsTo::class);
    expect((new DealNote)->user())->toBeInstanceOf(BelongsTo::class);
    expect((new Activity)->type())->toBeInstanceOf(BelongsTo::class);
    expect((new Activity)->company())->toBeInstanceOf(BelongsTo::class);
    expect((new Invite)->status())->toBeInstanceOf(BelongsTo::class);
    expect((new Invite)->role())->toBeInstanceOf(BelongsTo::class);
    expect((new WhatsappConnection)->user())->toBeInstanceOf(BelongsTo::class);
    expect((new WhatsappConnection)->status())->toBeInstanceOf(BelongsTo::class);
    expect((new WhatsappConnection)->messages())->toBeInstanceOf(HasMany::class);
    expect((new Message)->lead())->toBeInstanceOf(BelongsTo::class);
    expect((new Message)->deal())->toBeInstanceOf(BelongsTo::class);
    expect((new Message)->connection())->toBeInstanceOf(BelongsTo::class);
    expect((new PipelineStage)->deals())->toBeInstanceOf(HasMany::class);
    expect((new Role)->users())->toBeInstanceOf(HasMany::class);
});
