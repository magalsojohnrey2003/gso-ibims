<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    protected $fillable = ['name', 'category_code', 'parent_id'];

    /**
     * Get the parent category (PPE) for a GLA.
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    /**
     * Get all GLA sub-categories for a PPE.
     */
    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    /**
     * Check if this category is a PPE (parent).
     */
    public function isPPE(): bool
    {
        return is_null($this->parent_id);
    }

    /**
     * Check if this category is a GLA (child).
     */
    public function isGLA(): bool
    {
        return !is_null($this->parent_id);
    }
}
