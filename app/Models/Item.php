<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Item extends Model
{
    use HasFactory;
    protected $fillable = [
        'name',
        'description',
        'category',
        'total_qty',
        'available_qty',
        'photo',
        'acquisition_date',
        'acquisition_cost',
        'is_borrowable',
    ];

    protected $casts = [
        'acquisition_date' => 'date',
        'acquisition_cost' => 'integer',
    ];

    public function borrowItems()
    {
        return $this->hasMany(BorrowRequestItem::class);
    }
    public function instances()
    {
        return $this->hasMany(ItemInstance::class);
    }

    /**
     * Get the category that the item belongs to.
     * Note: The category field can store either category ID or category name
     */
    public function categoryModel()
    {
        // Try to determine if category is an ID or name
        if (is_numeric($this->category)) {
            return $this->belongsTo(Category::class, 'category', 'id');
        }
        return $this->belongsTo(Category::class, 'category', 'name');
    }

    /**
     * Get the category name regardless of whether category field stores ID or name
     */
    public function getCategoryNameAttribute()
    {
        if (is_numeric($this->category)) {
            $category = Category::find($this->category);
            return $category ? $category->name : $this->category;
        }
        return $this->category;
    }

    /**
     * Resolve the public URL of the item's photo or return the default placeholder.
     */
    public function getPhotoUrlAttribute(): string
    {
        $photo = is_scalar($this->photo) ? (string) $this->photo : '';

        if ($photo !== '') {
            if (Str::startsWith($photo, ['http://', 'https://'])) {
                return $photo;
            }

            if (Storage::disk('public')->exists($photo)) {
                return url('storage/' . ltrim($photo, '/'));
            }

            if (file_exists(public_path($photo))) {
                return url($photo);
            }
        }

        return asset('images/item.png');
    }

}
