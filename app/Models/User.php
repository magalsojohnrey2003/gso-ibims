<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use App\Models\BorrowRequest;
use App\Models\BorrowItemInstance;
use Illuminate\Support\Facades\DB;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasRoles, SoftDeletes;

    public const DAMAGE_INCIDENT_STATUSES = ['damage', 'damaged', 'minor_damage', 'missing'];

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        // 'name', // optional
        'role',
        'first_name',
        'middle_name',
        'last_name',
        'phone',
        'address',
        'email',
        'password',
        'last_login_at',
        'creation_source',
        'borrowing_status',
        'terms_accepted_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'last_login_at' => 'datetime',
            'terms_accepted_at' => 'datetime',
        ];
    }

    public function getFullNameAttribute()
    {
        return trim($this->first_name . ' ' . ($this->middle_name ? $this->middle_name . ' ' : '') . $this->last_name);
    }

    public function borrowRequests()
    {
        return $this->hasMany(BorrowRequest::class);
    }

    public function borrowItemInstances()
    {
        return $this->hasManyThrough(BorrowItemInstance::class, BorrowRequest::class);
    }

    public function damageIncidents()
    {
        $statuses = array_map('strtolower', self::DAMAGE_INCIDENT_STATUSES);

        return $this->borrowItemInstances()
            ->whereNotNull('return_condition')
            ->whereIn(DB::raw('LOWER(return_condition)'), $statuses);
    }

    public function getBorrowingStatusLabelAttribute(): string
    {
        return match ($this->borrowing_status) {
            'good' => 'Good',
            'fair', 'under_review' => 'Fair',
            'risk', 'restricted', 'suspended' => 'Risk',
            default => 'Good',
        };
    }
}
