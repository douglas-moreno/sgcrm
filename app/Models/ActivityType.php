<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\ActivityTypeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class ActivityType extends Model
{
    /** @use HasFactory<ActivityTypeFactory> */
    use HasFactory;

    public const LEAD_CREATED = 'lead_created';

    public const LEAD_UPDATED = 'lead_updated';

    public const LEAD_REASSIGNED = 'lead_reassigned';

    public const DEAL_CREATED = 'deal_created';

    public const DEAL_UPDATED = 'deal_updated';

    public const STAGE_CHANGED = 'stage_changed';

    public const VALUE_CHANGED = 'value_changed';

    public const OWNERSHIP_CHANGED = 'ownership_changed';

    public const NOTE_ADDED = 'note_added';

    public const MESSAGE_SENT = 'message_sent';

    public const MESSAGE_RECEIVED = 'message_received';

    public const DEAL_WON = 'deal_won';

    public const DEAL_LOST = 'deal_lost';

    protected $fillable = ['name', 'slug', 'description', 'is_active'];

    public function activities(): HasMany
    {
        return $this->hasMany(Activity::class);
    }

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }
}
