<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\DealNoteFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class DealNote extends Model
{
    /** @use HasFactory<DealNoteFactory> */
    use HasFactory;

    protected $fillable = ['deal_id', 'user_id', 'body'];

    public function deal(): BelongsTo
    {
        return $this->belongsTo(Deal::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
