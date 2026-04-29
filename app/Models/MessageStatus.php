<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\MessageStatusFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class MessageStatus extends Model
{
    /** @use HasFactory<MessageStatusFactory> */
    use HasFactory;

    public const PENDING = 'pending';

    public const SENT = 'sent';

    public const DELIVERED = 'delivered';

    public const READ = 'read';

    public const FAILED = 'failed';

    protected $fillable = ['name', 'slug'];

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class, 'status_id');
    }
}
