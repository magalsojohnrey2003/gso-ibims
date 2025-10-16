<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BorrowRequest extends Model
{
    protected $fillable = ['user_id', 'borrow_date', 'return_date', 'manpower_count','location', 'status', 
        'delivery_status',
        'dispatched_at',
        'delivered_at',
        'delivery_reported_at',
        'delivery_report_reason',];
        

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function items()
    {
        return $this->hasMany(BorrowRequestItem::class);
    }

    public function returnRequests()
    {
        return $this->hasMany(ReturnRequest::class);
    }

    public function borrowedInstances()
    {
        return $this->hasMany(BorrowItemInstance::class);
    }

    protected $casts = [
        'borrow_date' => 'date',
        'return_date' => 'date',
        'dispatched_at' => 'datetime',
        'delivered_at' => 'datetime',
        'delivery_reported_at' => 'datetime',
    ];

     /* ---------- helpers for delivery lifecycle ---------- */

    public function isDispatched(): bool
    {
        return $this->delivery_status === 'dispatched';
    }

    public function isDelivered(): bool
    {
        return $this->delivery_status === 'delivered';
    }

    public function markDispatched()
    {
        $this->delivery_status = 'dispatched';
        $this->dispatched_at = now();
        $this->save();
    }

    public function markDelivered()
    {
        $this->delivery_status = 'delivered';
        $this->delivered_at = now();
        $this->save();
    }

    public function markNotReceived(?string $reason = null)
    {
        $this->delivery_status = 'not_received';
        $this->delivery_reported_at = now();
        $this->delivery_report_reason = $reason;
        $this->save();
    }
}
