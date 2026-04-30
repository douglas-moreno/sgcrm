<?php

declare(strict_types=1);

namespace App\Models;

use App\Mail\PasswordResetMail;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Mail;

final class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'company_id',
        'role_id',
        'name',
        'email',
        'email_verified_at',
        'password',
        'avatar_path',
        'is_active',
        'must_change_password',
        'last_login_at',
    ];

    protected $hidden = ['password', 'remember_token'];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function ownedLeads(): HasMany
    {
        return $this->hasMany(Lead::class, 'owner_user_id');
    }

    public function ownedDeals(): HasMany
    {
        return $this->hasMany(Deal::class, 'owner_user_id');
    }

    public function whatsappConnection(): HasOne
    {
        return $this->hasOne(WhatsappConnection::class);
    }

    public function dealNotes(): HasMany
    {
        return $this->hasMany(DealNote::class);
    }

    public function activities(): HasMany
    {
        return $this->hasMany(Activity::class);
    }

    public function isBusinessOwner(): bool
    {
        return $this->roleSlug() === Role::BUSINESS_OWNER;
    }

    public function isSalesperson(): bool
    {
        return $this->roleSlug() === Role::SALESPERSON;
    }

    public function roleSlug(): ?string
    {
        return $this->role?->slug ?? null;
    }

    public function sendPasswordResetNotification($token): void
    {
        Mail::to($this->email)->queue(new PasswordResetMail($this, (string) $token));
    }

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'must_change_password' => 'boolean',
        ];
    }
}
