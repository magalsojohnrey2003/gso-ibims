<?php

namespace Tests\Feature;

use App\Models\BorrowItemInstance;
use App\Models\BorrowRequest;
use App\Models\BorrowRequestItem;
use App\Models\Item;
use App\Models\ItemInstance;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AdminDispatchDeliveryStockTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function stock_is_deducted_only_on_delivery_not_dispatch()
    {
        \Illuminate\Support\Facades\Notification::fake();
        \Illuminate\Support\Facades\Event::fake();

        $admin = User::factory()->create(['role' => 'admin']);
        $borrower = User::factory()->create();
        $this->actingAs($admin);

        $item = Item::factory()->create(['total_qty' => 8, 'available_qty' => 8]);

        $request = BorrowRequest::create([
            'user_id' => $borrower->id,
            'borrow_date' => now()->toDateString(),
            'return_date' => now()->addDays(3)->toDateString(),
            'status' => 'validated',
        ]);

        BorrowRequestItem::create([
            'borrow_request_id' => $request->id,
            'item_id' => $item->id,
            'quantity' => 3,
        ]);

        ItemInstance::factory()->count(8)->available()->forItem($item)->create();

        // Dispatch (allocation only)
        $dispatchRes = $this->postJson('/admin/borrow-requests/' . $request->id . '/dispatch');
        $dispatchRes->assertStatus(200);
        $request->refresh();
        $item->refresh();
        $this->assertEquals('dispatched', $request->delivery_status);
        $this->assertEquals(8, $item->available_qty, 'Stock unchanged after dispatch');

        $allocated = BorrowItemInstance::where('borrow_request_id', $request->id)->get();
        $this->assertCount(3, $allocated);
        foreach ($allocated as $row) {
            $this->assertEquals('allocated', $row->instance->status);
        }

        // Deliver (deducts stock & flips instance status to borrowed)
        $deliverRes = $this->postJson('/admin/borrow-requests/' . $request->id . '/deliver');
        $deliverRes->assertStatus(200);
        $item->refresh();
        $request->refresh();
        $this->assertEquals('delivered', $request->delivery_status);
        $this->assertEquals(5, $item->available_qty, 'Stock deducted exactly by borrowed quantity after delivery');
        foreach ($allocated as $row) {
            $row->refresh();
            $this->assertEquals('borrowed', $row->instance->status);
        }
    }
}
