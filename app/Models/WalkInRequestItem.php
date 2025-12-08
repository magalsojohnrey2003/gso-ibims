<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WalkInRequestItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'walk_in_request_id',
        'item_id',
        'quantity',
        'received_quantity',
    ];

    public function request(): BelongsTo
    {
        return $this->belongsTo(WalkInRequest::class, 'walk_in_request_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class, 'item_id');
    }
}
