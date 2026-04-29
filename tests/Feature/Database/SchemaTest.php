<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schema;

it('creates all lookup tables with the expected columns', function (string $table, array $columns): void {
    expect(Schema::hasTable($table))->toBeTrue();
    expect(Schema::hasColumns($table, $columns))->toBeTrue();
})->with([
    ['roles', ['id', 'name', 'slug', 'description', 'is_active', 'created_at', 'updated_at']],
    ['pipeline_stages', ['id', 'name', 'slug', 'position', 'is_terminal', 'is_won', 'is_lost', 'is_active']],
    ['invite_statuses', ['id', 'name', 'slug', 'is_active']],
    ['whatsapp_connection_statuses', ['id', 'name', 'slug', 'is_active']],
    ['message_directions', ['id', 'name', 'slug']],
    ['message_statuses', ['id', 'name', 'slug']],
    ['message_types', ['id', 'name', 'slug']],
    ['activity_types', ['id', 'name', 'slug', 'is_active']],
]);

it('creates the core domain tables with the expected columns', function (string $table, array $columns): void {
    expect(Schema::hasTable($table))->toBeTrue();
    expect(Schema::hasColumns($table, $columns))->toBeTrue();
})->with([
    ['companies', ['id', 'name', 'created_at', 'updated_at']],
    ['users', ['id', 'company_id', 'role_id', 'name', 'email', 'avatar_path', 'is_active', 'must_change_password', 'last_login_at', 'deleted_at']],
    ['invites', ['id', 'company_id', 'invited_by_user_id', 'role_id', 'status_id', 'name', 'email', 'token', 'expires_at', 'accepted_at', 'accepted_user_id']],
    ['leads', ['id', 'company_id', 'owner_user_id', 'name', 'email', 'phone', 'notes', 'deleted_at']],
    ['deals', ['id', 'company_id', 'lead_id', 'owner_user_id', 'stage_id', 'title', 'value', 'loss_reason', 'won_at', 'lost_at', 'deleted_at']],
    ['deal_notes', ['id', 'deal_id', 'user_id', 'body']],
    ['activities', ['id', 'company_id', 'activity_type_id', 'user_id', 'lead_id', 'deal_id', 'before_value', 'after_value', 'metadata', 'created_at']],
    ['whatsapp_connections', ['id', 'user_id', 'company_id', 'status_id', 'instance_name', 'phone_number', 'qr_code_path', 'connected_at', 'disconnected_at', 'last_checked_at', 'webhook_secret']],
    ['messages', ['id', 'company_id', 'whatsapp_connection_id', 'lead_id', 'deal_id', 'user_id', 'direction_id', 'status_id', 'message_type_id', 'external_id', 'body', 'media_path']],
]);

it('enforces lead email uniqueness scoped per company', function (): void {
    $company = App\Models\Company::factory()->create();
    $owner = App\Models\User::factory()->salesperson()->forCompany($company)->create();

    App\Models\Lead::factory()->create([
        'company_id' => $company->id,
        'owner_user_id' => $owner->id,
        'email' => 'duplicate@example.com',
    ]);

    expect(fn () => App\Models\Lead::factory()->create([
        'company_id' => $company->id,
        'owner_user_id' => $owner->id,
        'email' => 'duplicate@example.com',
    ]))->toThrow(Illuminate\Database\QueryException::class);
});

it('allows the same lead email across different companies', function (): void {
    $companyA = App\Models\Company::factory()->create();
    $companyB = App\Models\Company::factory()->create();
    $ownerA = App\Models\User::factory()->salesperson()->forCompany($companyA)->create();
    $ownerB = App\Models\User::factory()->salesperson()->forCompany($companyB)->create();

    App\Models\Lead::factory()->create([
        'company_id' => $companyA->id,
        'owner_user_id' => $ownerA->id,
        'email' => 'shared@example.com',
    ]);

    App\Models\Lead::factory()->create([
        'company_id' => $companyB->id,
        'owner_user_id' => $ownerB->id,
        'email' => 'shared@example.com',
    ]);

    expect(App\Models\Lead::where('email', 'shared@example.com')->count())->toBe(2);
});

it('enforces one whatsapp connection per user', function (): void {
    $user = App\Models\User::factory()->salesperson()->create();

    App\Models\WhatsappConnection::factory()->create(['user_id' => $user->id, 'company_id' => $user->company_id]);

    expect(fn () => App\Models\WhatsappConnection::factory()->create([
        'user_id' => $user->id,
        'company_id' => $user->company_id,
    ]))->toThrow(Illuminate\Database\QueryException::class);
});
