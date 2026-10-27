<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'match_id', 'channel', 'status', 'sent_at', 'error'])]
class Notification extends Model
{
    protected $primaryKey = 'notification_id';

    protected $attributes = [
        'status' => 'queued',
    ];

    public const UPDATED_AT = null;

    protected function casts(): array
    {
        return [
            'sent_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    public function itemMatch(): BelongsTo
    {
        return $this->belongsTo(ItemMatch::class, 'match_id', 'match_id');
    }
}
