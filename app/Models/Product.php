<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id', 'name', 'sku', 'description', 'price',
        'quantity', 'reorder_level', 'is_active',
    ];

    protected function casts(): array
    {
        return ['price' => 'decimal:2', 'is_active' => 'boolean'];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function scopeLowStock(Builder $query): Builder
    {
        return $query->whereColumn('quantity', '<=', 'reorder_level');
    }

    public function suppliers(): BelongsToMany
    {
        return $this->belongsToMany(Supplier::class)->withTimestamps();
    }
}
