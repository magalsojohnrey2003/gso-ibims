<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\Item;
use App\Models\ItemInstance;
use App\Models\BorrowRequest;
use App\Models\BorrowRequestItem;
use App\Models\BorrowItemInstance;
use PHPUnit\Framework\Attributes\Test;

class AdminCancelDispatchTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate');
    }

    #[Test]
    public function cancel_dispatch_rolls_back_allocations_and_delivery_status()
    {
        \Illuminate\Support\Facades\Notification::fake();
        \Illuminate\Support\Facades\Event::fake();

        $borrower = User::factory()->create();
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);

        $item = Item::factory()->create([
            'total_qty' => 4,
            'available_qty' => 4,
        ]);

        $request = BorrowRequest::create([
            'user_id' => $borrower->id,
            'borrow_date' => now()->toDateString(),
            'return_date' => now()->addDay()->toDateString(),
            'status' => 'validated',
        ]);

        $reqItem = BorrowRequestItem::create([
            'borrow_request_id' => $request->id,
            'item_id' => $item->id,
            'quantity' => 2,
        ]);

        ItemInstance::factory()->count(4)->available()->forItem($item)->create();

        // Dispatch
        $dispatchRes = $this->postJson('/admin/borrow-requests/' . $request->id . '/dispatch');
        $dispatchRes->assertStatus(200)->assertJson(['message' => 'Items dispatched successfully.']);
        $request->refresh();
        $this->assertEquals('dispatched', $request->delivery_status);
        $allocRows = BorrowItemInstance::where('borrow_request_id', $request->id)->get();
        $this->assertCount(2, $allocRows);

        foreach ($allocRows as $row) {
            $this->assertEquals('allocated', $row->instance->status);
        }

        // Cancel dispatch
        $cancelRes = $this->postJson('/admin/borrow-requests/' . $request->id . '/cancel-dispatch');
        $cancelRes->assertStatus(200)->assertJson(['message' => 'Dispatch canceled and allocations rolled back.']);
        $request->refresh();

        $this->assertNull($request->delivery_status, 'delivery_status cleared');
        $this->assertNull($request->dispatched_at, 'dispatched_at cleared');

        // Rows removed
        $this->assertEquals(0, BorrowItemInstance::where('borrow_request_id', $request->id)->count(), 'BorrowItemInstance rows removed');

        // Instances returned to available
        $instanceStatuses = ItemInstance::where('item_id', $item->id)->pluck('status')->all();
        $this->assertEqualsCanonicalizing(['available','available','available','available'], $instanceStatuses);

        // Stock unchanged (not deducted yet)
        $item->refresh();
        $this->assertEquals(4, $item->available_qty, 'Stock remains same after cancel dispatch');
    }
}
