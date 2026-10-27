<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['claim_id', 'handed_by', 'received_by', 'handover_date', 'photo', 'note'])]
class Handover extends Model
{
    protected $primaryKey = 'handover_id';

    protected function casts(): array
    {
        return [
            'handover_date' => 'datetime',
        ];
    }

    public function claim(): BelongsTo
    {
        return $this->belongsTo(Claim::class, 'claim_id', 'claim_id');
    }

    public function handedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handed_by', 'user_id');
    }

    public function receivedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by', 'user_id');
    }
}
