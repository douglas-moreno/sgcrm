<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Database\Factories\MessageFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class Message extends Model
{
    /** @use HasFactory<MessageFactory> */
    use BelongsToCompany, HasFactory;

    protected $fillable = [
        'company_id',
        'whatsapp_connection_id',
        'lead_id',
        'deal_id',
        'user_id',
        'direction_id',
        'status_id',
        'message_type_id',
        'external_id',
        'body',
        'media_path',
        'sent_at',
        'delivered_at',
        'read_at',
        'failed_at',
        'error_message',
        'created_at',
    ];

    public function connection(): BelongsTo
    {
        return $this->belongsTo(WhatsappConnection::class, 'whatsapp_connection_id');
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function deal(): BelongsTo
    {
        return $this->belongsTo(Deal::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function direction(): BelongsTo
    {
        return $this->belongsTo(MessageDirection::class, 'direction_id');
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo(MessageStatus::class, 'status_id');
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(MessageType::class, 'message_type_id');
    }

    protected function casts(): array
    {
        return [
            'sent_at' => 'datetime',
            'delivered_at' => 'datetime',
            'read_at' => 'datetime',
            'failed_at' => 'datetime',
        ];
    }
}
