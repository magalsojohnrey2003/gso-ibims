<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ManpowerRole extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
    ];

    public function requests()
    {
        return $this->hasMany(ManpowerRequest::class, 'manpower_role_id');
    }
}
