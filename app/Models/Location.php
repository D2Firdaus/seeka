<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'slug', 'description'])]
class Location extends Model
{
    protected $primaryKey = 'location_id';

    public function items(): HasMany
    {
        return $this->hasMany(Item::class, 'location_id', 'location_id');
    }
}
