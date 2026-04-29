<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Database\Factories\InviteFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class Invite extends Model
{
    /** @use HasFactory<InviteFactory> */
    use BelongsToCompany, HasFactory;

    protected $fillable = [
        'company_id',
        'invited_by_user_id',
        'role_id',
        'status_id',
        'name',
        'email',
        'token',
        'expires_at',
        'accepted_at',
        'accepted_user_id',
    ];

    public function invitedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by_user_id');
    }

    public function acceptedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'accepted_user_id');
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo(InviteStatus::class, 'status_id');
    }

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'accepted_at' => 'datetime',
        ];
    }
}
