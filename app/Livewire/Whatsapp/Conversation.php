<?php

declare(strict_types=1);

namespace App\Livewire\Whatsapp;

use App\Models\ActivityType;
use App\Models\Deal;
use App\Models\Lead;
use App\Models\Message;
use App\Models\MessageDirection;
use App\Models\MessageStatus;
use App\Models\MessageType;
use App\Models\WhatsappConnection;
use App\Models\WhatsappConnectionStatus;
use App\Services\Activity\ActivityRecorder;
use App\Services\Evolution\EvolutionClient;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
final class Conversation extends Component
{
    public Lead $lead;

    public ?int $dealId = null;

    public string $body = '';

    public function mount(Lead $lead, ?int $deal = null): void
    {
        Gate::authorize('viewAny', [Message::class, $lead]);
        $this->lead = $lead;

        if ($deal !== null) {
            $dealModel = Deal::query()->findOrFail($deal);
            Gate::authorize('view', $dealModel);
            $this->dealId = $dealModel->id;
        }
    }

    public function send(EvolutionClient $client, ActivityRecorder $recorder): void
    {
        Gate::authorize('create', [Message::class, $this->lead]);

        $connection = $this->connection;

        if ($connection === null || $connection->status?->slug !== WhatsappConnectionStatus::CONNECTED) {
            $this->addError('body', 'WhatsApp is disconnected. Reconnect to send messages.');

            return;
        }

        if (empty($this->lead->phone)) {
            $this->addError('body', 'Lead has no phone number.');

            return;
        }

        $data = $this->validate(rules: [
            'body' => ['required', 'string', 'max:5000'],
        ]);

        $direction = MessageDirection::query()->where('slug', MessageDirection::OUTBOUND)->firstOrFail();
        $pending = MessageStatus::query()->where('slug', MessageStatus::PENDING)->firstOrFail();
        $type = MessageType::query()->where('slug', MessageType::TEXT)->firstOrFail();

        /** @var \App\Models\User $user */
        $user = auth()->user();

        $message = Message::create([
            'company_id' => $connection->company_id,
            'whatsapp_connection_id' => $connection->id,
            'lead_id' => $this->lead->id,
            'deal_id' => $this->dealId,
            'user_id' => $user->id,
            'direction_id' => $direction->id,
            'status_id' => $pending->id,
            'message_type_id' => $type->id,
            'body' => $data['body'],
        ]);

        $response = $client->sendTextMessage($connection->instance_name, $this->lead->phone, $data['body']);

        if ($response->successful()) {
            $sent = MessageStatus::query()->where('slug', MessageStatus::SENT)->firstOrFail();
            $message->update([
                'status_id' => $sent->id,
                'external_id' => $response->json('key.id'),
                'sent_at' => Carbon::now(),
            ]);
        } else {
            $failed = MessageStatus::query()->where('slug', MessageStatus::FAILED)->firstOrFail();
            $message->update([
                'status_id' => $failed->id,
                'failed_at' => Carbon::now(),
                'error_message' => $response->body(),
            ]);
        }

        if ($this->dealId !== null) {
            $recorder->record(ActivityType::MESSAGE_SENT, [
                'company_id' => $connection->company_id,
                'lead' => $this->lead,
                'deal_id' => $this->dealId,
                'metadata' => ['external_id' => $message->external_id, 'preview' => mb_substr($data['body'], 0, 80)],
            ]);
        }

        $this->reset('body');
        $this->dispatch('message-sent', messageId: $message->id);
    }

    #[Computed]
    public function connection(): ?WhatsappConnection
    {
        return WhatsappConnection::query()
            ->where('user_id', auth()->id())
            ->with('status')
            ->first();
    }

    #[Computed]
    public function isConnected(): bool
    {
        return $this->connection?->status?->slug === WhatsappConnectionStatus::CONNECTED;
    }

    #[Computed]
    public function messageList()
    {
        return Message::query()
            ->where('lead_id', $this->lead->id)
            ->with(['direction', 'status', 'user'])
            ->orderBy('created_at')
            ->get();
    }

    public function render(): View
    {
        return view('livewire.whatsapp.conversation');
    }
}
