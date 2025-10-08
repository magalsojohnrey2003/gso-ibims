<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReturnRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'borrow_request_id',
        'user_id',
        'condition',
        'damage_reason',
        'status',
        'processed_by',
    ];

    // Relationships
    public function borrowRequest()
    {
        return $this->belongsTo(BorrowRequest::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function admin()
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    public function returnItems()
    {
        return $this->hasMany(ReturnItem::class);
    }

}
