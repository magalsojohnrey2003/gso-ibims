<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class BorrowItemInstance extends Model
{
    protected $fillable = [
        'borrow_request_id','item_id','item_instance_id',
        'checked_out_at','expected_return_at','returned_at'
    ];

    public function borrowRequest()
    {
        return $this->belongsTo(BorrowRequest::class);
    }

    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    public function instance()
    {
        return $this->belongsTo(ItemInstance::class, 'item_instance_id');
    }
}
