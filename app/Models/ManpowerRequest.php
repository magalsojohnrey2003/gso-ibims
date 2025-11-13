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
        'approved_quantity',
        'manpower_role_id',
        'public_token',
    ];

    protected $casts = [
        'start_at' => 'datetime',
        'end_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function roleType()
    {
        return $this->belongsTo(ManpowerRole::class, 'manpower_role_id');
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

    public function getPublicStatusUrlAttribute(): ?string
    {
        if (! $this->public_token) return null;
        try {
            return route('manpower.requests.public.show', $this->public_token);
        } catch (\Throwable $e) {
            return null;
        }
    }
}
