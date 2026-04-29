<?php

declare(strict_types=1);

use App\Models\ActivityType;
use App\Models\InviteStatus;
use App\Models\MessageDirection;
use App\Models\MessageStatus;
use App\Models\MessageType;
use App\Models\PipelineStage;
use App\Models\Role;
use App\Models\WhatsappConnectionStatus;

it('seeds every lookup table with its canonical slugs', function (): void {
    $this->seed();

    expect(Role::pluck('slug')->all())
        ->toContain(Role::BUSINESS_OWNER, Role::SALESPERSON);

    expect(PipelineStage::pluck('slug')->all())
        ->toContain(
            PipelineStage::NEW_LEAD,
            PipelineStage::CONTACTED,
            PipelineStage::PROPOSAL_SENT,
            PipelineStage::NEGOTIATION,
            PipelineStage::WON,
            PipelineStage::LOST,
        );

    expect(InviteStatus::pluck('slug')->all())
        ->toContain(InviteStatus::PENDING, InviteStatus::ACCEPTED, InviteStatus::EXPIRED, InviteStatus::REVOKED);

    expect(WhatsappConnectionStatus::pluck('slug')->all())
        ->toContain(
            WhatsappConnectionStatus::DISCONNECTED,
            WhatsappConnectionStatus::PENDING,
            WhatsappConnectionStatus::CONNECTED,
            WhatsappConnectionStatus::FAILED,
        );

    expect(MessageDirection::pluck('slug')->all())
        ->toContain(MessageDirection::INBOUND, MessageDirection::OUTBOUND);

    expect(MessageStatus::pluck('slug')->all())
        ->toContain(
            MessageStatus::PENDING,
            MessageStatus::SENT,
            MessageStatus::DELIVERED,
            MessageStatus::READ,
            MessageStatus::FAILED,
        );

    expect(MessageType::pluck('slug')->all())
        ->toContain(
            MessageType::TEXT,
            MessageType::IMAGE,
            MessageType::AUDIO,
            MessageType::VIDEO,
            MessageType::DOCUMENT,
            MessageType::LOCATION,
        );

    expect(ActivityType::pluck('slug')->all())
        ->toContain(
            ActivityType::LEAD_CREATED,
            ActivityType::DEAL_CREATED,
            ActivityType::STAGE_CHANGED,
            ActivityType::DEAL_LOST,
            ActivityType::MESSAGE_SENT,
        );
});

it('marks Won and Lost stages with their boolean flags', function (): void {
    $this->seed();

    $won = PipelineStage::where('slug', PipelineStage::WON)->firstOrFail();
    $lost = PipelineStage::where('slug', PipelineStage::LOST)->firstOrFail();

    expect($won->is_won)->toBeTrue();
    expect($won->is_terminal)->toBeTrue();
    expect($lost->is_lost)->toBeTrue();
    expect($lost->is_terminal)->toBeTrue();
});
