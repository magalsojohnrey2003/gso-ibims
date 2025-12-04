<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BorrowRequest extends Model
{
    use HasFactory;

    protected $appends = ['formatted_request_id'];

    protected $fillable = [
        'user_id',
        'borrow_date',
        'return_date',
        'time_of_usage',
        'purpose_office',
        'purpose',
        'manpower_count',
        'manpower_adjustment_reason',
        'location',
        'letter_path',
        'qr_verified_form_path',
        'status',
        'delivery_status',
        'dispatched_at',
        'delivered_at',
        'delivery_reported_at',
        'delivery_report_reason',
        'delivery_reason_type',
        'delivery_reason_details',
        'rejection_reason_id',
        'reject_category',
        'reject_reason',
        'return_proof_path',
        'return_proof_notes',
    ];
        

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function items()
    {
        return $this->hasMany(BorrowRequestItem::class);
    }

    public function borrowedInstances()
    {
        return $this->hasMany(BorrowItemInstance::class);
    }

    public function rejectionTemplate()
    {
        return $this->belongsTo(RejectionReason::class, 'rejection_reason_id');
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

    public function getFormattedRequestIdAttribute(): ?string
    {
        $id = $this->getAttribute('id');
        if (!$id) {
            return null;
        }
        return sprintf('BR-%04d', (int) $id);
    }
}
