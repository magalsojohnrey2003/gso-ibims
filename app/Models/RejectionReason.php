<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class RejectionReason extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'subject',
        'detail',
        'usage_count',
        'created_by',
    ];

    protected $casts = [
        'usage_count' => 'integer',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}

