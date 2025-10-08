<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ItemInstanceEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'item_instance_id',
        'item_id',
        'actor_id',
        'actor_name',
        'actor_type',
        'action',
        'payload',
        'performed_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'performed_at' => 'datetime',
    ];

    public function instance(): BelongsTo
    {
        return $this->belongsTo(ItemInstance::class, 'item_instance_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
