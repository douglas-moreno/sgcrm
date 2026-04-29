<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\MessageDirectionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class MessageDirection extends Model
{
    /** @use HasFactory<MessageDirectionFactory> */
    use HasFactory;

    public const INBOUND = 'inbound';

    public const OUTBOUND = 'outbound';

    protected $fillable = ['name', 'slug'];

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class, 'direction_id');
    }
}
