<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WalkInRequest extends Model
{
    use HasFactory;

    protected $appends = ['formatted_request_id'];

    protected $fillable = [
        'borrower_name',
        'office_agency',
        'contact_number',
        'address',
        'purpose',
        'borrowed_at',
        'returned_at',
        'status',
        'created_by',
    ];

    protected $casts = [
        'borrowed_at' => 'datetime',
        'returned_at' => 'datetime',
    ];

    /**
     * Check if the request is pending approval
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if the request is approved
     */
    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    /**
     * Check if the request is delivered
     */
    public function isDelivered(): bool
    {
        return $this->status === 'delivered';
    }

    /**
     * Get status badge HTML
     */
    public function getStatusBadgeAttribute(): string
    {
        $badges = [
            'pending' => '<span class="inline-flex items-center gap-1 px-2 py-0.5 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800"><i class="fas fa-clock"></i> Pending</span>',
            'approved' => '<span class="inline-flex items-center gap-1 px-2 py-0.5 text-xs font-semibold rounded-full bg-green-100 text-green-800"><i class="fas fa-check-circle"></i> Approved</span>',
            'delivered' => '<span class="inline-flex items-center gap-1 px-2 py-0.5 text-xs font-semibold rounded-full bg-blue-100 text-blue-800"><i class="fas fa-box-check"></i> Delivered</span>',
        ];

        return $badges[$this->status] ?? '<span class="inline-flex items-center gap-1 px-2 py-0.5 text-xs font-semibold rounded-full bg-gray-100 text-gray-700"><i class="fas fa-question-circle"></i> Unknown</span>';
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(WalkInRequestItem::class);
    }

    public function getFormattedRequestIdAttribute(): ?string
    {
        $id = $this->getAttribute('id');
        if (!$id) {
            return null;
        }
        return sprintf('WI-%04d', (int) $id);
    }
}
