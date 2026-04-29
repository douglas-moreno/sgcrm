<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\WhatsappConnectionStatusFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class WhatsappConnectionStatus extends Model
{
    /** @use HasFactory<WhatsappConnectionStatusFactory> */
    use HasFactory;

    public const DISCONNECTED = 'disconnected';

    public const PENDING = 'pending';

    public const CONNECTED = 'connected';

    public const FAILED = 'failed';

    protected $fillable = ['name', 'slug', 'is_active'];

    public function connections(): HasMany
    {
        return $this->hasMany(WhatsappConnection::class, 'status_id');
    }

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }
}
