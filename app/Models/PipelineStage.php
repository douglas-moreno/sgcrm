<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\PipelineStageFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class PipelineStage extends Model
{
    /** @use HasFactory<PipelineStageFactory> */
    use HasFactory;

    public const NEW_LEAD = 'new_lead';

    public const CONTACTED = 'contacted';

    public const PROPOSAL_SENT = 'proposal_sent';

    public const NEGOTIATION = 'negotiation';

    public const WON = 'won';

    public const LOST = 'lost';

    protected $fillable = [
        'name',
        'slug',
        'position',
        'is_terminal',
        'is_won',
        'is_lost',
        'description',
        'is_active',
    ];

    public function deals(): HasMany
    {
        return $this->hasMany(Deal::class, 'stage_id');
    }

    protected function casts(): array
    {
        return [
            'position' => 'integer',
            'is_terminal' => 'boolean',
            'is_won' => 'boolean',
            'is_lost' => 'boolean',
            'is_active' => 'boolean',
        ];
    }
}
