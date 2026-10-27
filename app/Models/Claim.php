<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable([
    'found_item_id',
    'claimant_id',
    'lost_item_id',
    'proof_description',
    'proof_image',
    'status',
    'verified_by',
    'verified_at',
    'admin_note',
])]
class Claim extends Model
{
    protected $primaryKey = 'claim_id';

    protected $attributes = [
        'status' => 'pending',
    ];

    protected function casts(): array
    {
        return [
            'verified_at' => 'datetime',
        ];
    }

    public function foundItem(): BelongsTo
    {
        return $this->belongsTo(Item::class, 'found_item_id', 'item_id');
    }

    public function lostItem(): BelongsTo
    {
        return $this->belongsTo(Item::class, 'lost_item_id', 'item_id');
    }

    public function claimant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'claimant_id', 'user_id');
    }

    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by', 'user_id');
    }

    public function handover(): HasOne
    {
        return $this->hasOne(Handover::class, 'claim_id', 'claim_id');
    }

    public function disputes(): HasMany
    {
        return $this->hasMany(Dispute::class, 'claim_id', 'claim_id');
    }
}
