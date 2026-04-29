<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Database\Factories\DealFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

final class Deal extends Model
{
    /** @use HasFactory<DealFactory> */
    use BelongsToCompany, HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id',
        'lead_id',
        'owner_user_id',
        'stage_id',
        'title',
        'value',
        'loss_reason',
        'won_at',
        'lost_at',
    ];

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    public function stage(): BelongsTo
    {
        return $this->belongsTo(PipelineStage::class, 'stage_id');
    }

    public function notes(): HasMany
    {
        return $this->hasMany(DealNote::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    public function activities(): HasMany
    {
        return $this->hasMany(Activity::class);
    }

    protected function casts(): array
    {
        return [
            'value' => 'decimal:2',
            'won_at' => 'datetime',
            'lost_at' => 'datetime',
        ];
    }
}
