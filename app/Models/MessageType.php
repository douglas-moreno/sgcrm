<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\MessageTypeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class MessageType extends Model
{
    /** @use HasFactory<MessageTypeFactory> */
    use HasFactory;

    public const TEXT = 'text';

    public const IMAGE = 'image';

    public const AUDIO = 'audio';

    public const VIDEO = 'video';

    public const DOCUMENT = 'document';

    public const LOCATION = 'location';

    protected $fillable = ['name', 'slug'];

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class, 'message_type_id');
    }
}
