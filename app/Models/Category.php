<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'slug'])]
class Category extends Model
{
    protected $primaryKey = 'category_id';

    public function items(): HasMany
    {
        return $this->hasMany(Item::class, 'category_id', 'category_id');
    }
}
