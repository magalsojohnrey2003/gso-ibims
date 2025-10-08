<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class ReturnItem extends Model
{
    protected $fillable = [
        'return_request_id','borrow_request_id','item_id','item_instance_id',
        'condition','remarks','photo','quantity'
    ];

      protected $casts = [
        'quantity' => 'integer',
    ];

    public function returnRequest()
    {
        return $this->belongsTo(ReturnRequest::class);
    }

    public function instance()
    {
        return $this->belongsTo(ItemInstance::class, 'item_instance_id');
    }

    public function item()
    {
        return $this->belongsTo(Item::class);
    }
}
