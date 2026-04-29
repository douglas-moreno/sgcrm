<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\InviteStatusFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class InviteStatus extends Model
{
    /** @use HasFactory<InviteStatusFactory> */
    use HasFactory;

    public const PENDING = 'pending';

    public const ACCEPTED = 'accepted';

    public const EXPIRED = 'expired';

    public const REVOKED = 'revoked';

    protected $fillable = ['name', 'slug', 'is_active'];

    public function invites(): HasMany
    {
        return $this->hasMany(Invite::class, 'status_id');
    }

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }
}
