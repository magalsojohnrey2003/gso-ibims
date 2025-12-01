<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BorrowRequestItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'borrow_request_id',
        'item_id',
        'quantity',
        'assigned_manpower',
        'manpower_role',
        'manpower_role_id',
        'manpower_notes',
        'assigned_by',
        'assigned_at',
        'is_manpower',
    ];

        
    public function request()
    {
        return $this->belongsTo(BorrowRequest::class, 'borrow_request_id');
    }

    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    protected $casts = [
        'assigned_manpower' => 'integer',
        'assigned_at' => 'datetime',
        'is_manpower' => 'boolean',
    ];

    public function manpowerRole()
    {
        return $this->belongsTo(ManpowerRole::class, 'manpower_role_id');
    }
}
