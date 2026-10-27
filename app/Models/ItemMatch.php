<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'lost_item_id',
    'found_item_id',
    'jw_score',
    'metadata_score',
    'bgem3_sparse',
    'bgem3_dense',
    'bge_reranker',
    'final_score',
    'pipeline_version',
    'status',
    'notified_at',
])]
class ItemMatch extends Model
{
    protected $primaryKey = 'match_id';

    protected $attributes = [
        'status' => 'candidate',
    ];

    protected function casts(): array
    {
        return [
            'jw_score' => 'decimal:4',
            'metadata_score' => 'decimal:4',
            'bgem3_sparse' => 'decimal:4',
            'bgem3_dense' => 'decimal:4',
            'bge_reranker' => 'decimal:4',
            'final_score' => 'decimal:4',
            'notified_at' => 'datetime',
        ];
    }

    public function lostItem(): BelongsTo
    {
        return $this->belongsTo(Item::class, 'lost_item_id', 'item_id');
    }

    public function foundItem(): BelongsTo
    {
        return $this->belongsTo(Item::class, 'found_item_id', 'item_id');
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class, 'match_id', 'match_id');
    }
}
