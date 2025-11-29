<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ManpowerRequestRole extends Model
{
    use HasFactory;

    protected $fillable = [
        'manpower_request_id',
        'manpower_role_id',
        'role_name',
        'quantity',
        'approved_quantity',
    ];

    public function request()
    {
        return $this->belongsTo(ManpowerRequest::class, 'manpower_request_id');
    }

    public function roleType()
    {
        return $this->belongsTo(ManpowerRole::class, 'manpower_role_id');
    }
}
