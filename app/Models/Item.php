<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'user_id',
    'category_id',
    'location_id',
    'type',
    'title',
    'description',
    'hidden_detail',
    'date_event',
    'status',
    'storage_location',
])]
#[Hidden(['hidden_detail', 'storage_location'])]
class Item extends Model
{
    use SoftDeletes;

    protected $primaryKey = 'item_id';

    protected $attributes = [
        'status' => 'open',
    ];

    protected function casts(): array
    {
        return [
            'date_event' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id', 'category_id');
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'location_id', 'location_id');
    }

    public function images(): HasMany
    {
        return $this->hasMany(ItemImage::class, 'item_id', 'item_id');
    }

    public function embeddings(): HasMany
    {
        return $this->hasMany(ItemEmbedding::class, 'item_id', 'item_id');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(ItemLog::class, 'item_id', 'item_id');
    }

    public function lostMatches(): HasMany
    {
        return $this->hasMany(ItemMatch::class, 'lost_item_id', 'item_id');
    }

    public function foundMatches(): HasMany
    {
        return $this->hasMany(ItemMatch::class, 'found_item_id', 'item_id');
    }

    public function claims(): HasMany
    {
        return $this->hasMany(Claim::class, 'found_item_id', 'item_id');
    }

    public function lostClaims(): HasMany
    {
        return $this->hasMany(Claim::class, 'lost_item_id', 'item_id');
    }
}
