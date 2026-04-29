<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\RoleFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Role extends Model
{
    /** @use HasFactory<RoleFactory> */
    use HasFactory;

    public const BUSINESS_OWNER = 'business_owner';

    public const SALESPERSON = 'salesperson';

    protected $fillable = ['name', 'slug', 'description', 'is_active'];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }
}
