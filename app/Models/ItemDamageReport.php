<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ItemDamageReport extends Model
{
    protected $fillable = [
        'item_instance_id',
        'borrow_request_id',
        'reported_by',
        'description',
        'photos',
        'status',
    ];

    protected $casts = [
        'photos' => 'array',
    ];

    public function itemInstance()
    {
        return $this->belongsTo(ItemInstance::class);
    }

    public function borrowRequest()
    {
        return $this->belongsTo(BorrowRequest::class);
    }

    public function reporter()
    {
        return $this->belongsTo(User::class, 'reported_by');
    }
}
