<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ManpowerRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'quantity',
        'role',
        'purpose',
        'location',
        'office_agency',
        'start_at',
        'end_at',
        'letter_path',
        'status',
        'rejection_reason_subject',
        'rejection_reason_detail',
    ];

    protected $casts = [
        'start_at' => 'datetime',
        'end_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getLetterUrlAttribute(): ?string
    {
        $path = $this->letter_path;
        if (! $path) return null;
        try {
            if (\Illuminate\Support\Facades\Storage::disk('public')->exists($path)) {
                return \Illuminate\Support\Facades\Storage::disk('public')->url($path);
            }
            if (filter_var($path, FILTER_VALIDATE_URL)) return $path;
        } catch (\Throwable $e) {}
        return null;
    }
}
