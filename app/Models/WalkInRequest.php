<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WalkInRequest extends Model
{
    protected $fillable = [
        'borrower_name',
        'office_agency',
        'contact_number',
        'address',
        'purpose',
        'borrowed_at',
        'returned_at',
        'created_by',
    ];

    protected $casts = [
        'borrowed_at' => 'datetime',
        'returned_at' => 'datetime',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(WalkInRequestItem::class);
    }
}
