<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Item extends Model
{
    use HasFactory;
    protected $fillable = [
        'name', 'category', 'total_qty', 'available_qty', 'photo'
    ];

    public function borrowItems()
    {
        return $this->hasMany(BorrowRequestItem::class);
    }
    public function instances()
    {
        return $this->hasMany(ItemInstance::class);
    }

}
