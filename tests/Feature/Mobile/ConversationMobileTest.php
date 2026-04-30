<?php

declare(strict_types=1);

use App\Livewire\Whatsapp\Conversation;
use App\Models\Company;
use App\Models\Lead;
use App\Models\User;
use App\Models\WhatsappConnection;
use App\Models\WhatsappConnectionStatus;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->seed(Database\Seeders\RoleSeeder::class);
    $this->seed(Database\Seeders\PipelineStageSeeder::class);
    $this->seed(Database\Seeders\WhatsappConnectionStatusSeeder::class);
    $this->seed(Database\Seeders\MessageDirectionSeeder::class);
    $this->seed(Database\Seeders\MessageStatusSeeder::class);
    $this->seed(Database\Seeders\MessageTypeSeeder::class);
    $this->seed(Database\Seeders\ActivityTypeSeeder::class);

    $this->company = Company::factory()->create();
    $this->seller = User::factory()->salesperson()->forCompany($this->company)->create();
    $this->lead = Lead::factory()
        ->forCompany($this->company)
        ->ownedBy($this->seller)
        ->create(['phone' => '5511999999999']);

    WhatsappConnection::factory()->create([
        'user_id' => $this->seller->id,
        'company_id' => $this->company->id,
        'status_id' => WhatsappConnectionStatus::where('slug', WhatsappConnectionStatus::CONNECTED)->value('id'),
    ]);
});

it('composer is sticky to bottom on mobile', function (): void {
    $html = Livewire::actingAs($this->seller)
        ->test(Conversation::class, ['lead' => $this->lead])
        ->html();

    expect($html)->toContain('sticky bottom-0')
        ->and($html)->toContain('sm:static');
});

it('messages list is scrollable + has Alpine auto-scroll hook', function (): void {
    $html = Livewire::actingAs($this->seller)
        ->test(Conversation::class, ['lead' => $this->lead])
        ->html();

    expect($html)->toContain('overflow-y-auto')
        ->and($html)->toContain('x-ref="messages"')
        ->and($html)->toContain('scrollBottom')
        ->and($html)->toContain('message-sent.window');
});
