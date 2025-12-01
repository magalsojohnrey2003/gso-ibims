<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BorrowItemInstance extends Model
{
    use HasFactory;

    protected $fillable = [
        'borrow_request_id',
        'item_id',
        'item_instance_id',
        'checked_out_at',
        'expected_return_at',
        'returned_at',
        'return_condition',
        'condition_updated_at',
        'walk_in_request_id',
        'borrowed_qty',
    ];

    protected $casts = [
        'checked_out_at' => 'datetime',
        'expected_return_at' => 'datetime',
        'returned_at' => 'datetime',
        'condition_updated_at' => 'datetime',
    ];

    public function borrowRequest()
    {
        return $this->belongsTo(BorrowRequest::class);
    }

    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    public function instance()
    {
        return $this->belongsTo(ItemInstance::class, 'item_instance_id');
    }

    public function walkInRequest()
    {
        return $this->belongsTo(WalkInRequest::class);
    }
}
