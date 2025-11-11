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

class AdminDispatchIdempotencyTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function dispatching_already_approved_request_is_graceful_and_does_not_reallocate_or_deduct_stock()
    {
        \Illuminate\Support\Facades\Notification::fake();
        \Illuminate\Support\Facades\Event::fake();

        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);

        $item = Item::factory()->create(['total_qty' => 5, 'available_qty' => 5]);

        $borrowRequest = BorrowRequest::create([
            'user_id' => $admin->id,
            'borrow_date' => now()->toDateString(),
            'return_date' => now()->addDays(2)->toDateString(),
            'status' => 'approved',
        ]);

        $bri = BorrowRequestItem::create([
            'borrow_request_id' => $borrowRequest->id,
            'item_id' => $item->id,
            'quantity' => 2,
        ]);

        // Pre-allocate 2 available instances and link them (approved => allocated already in normal flow)
        $instances = ItemInstance::factory()->count(2)->available()->forItem($item)->create();
        foreach ($instances as $inst) {
            $inst->status = 'allocated';
            $inst->save();
            BorrowItemInstance::create([
                'borrow_request_id' => $borrowRequest->id,
                'item_id' => $item->id,
                'item_instance_id' => $inst->id,
                'checked_out_at' => now(),
                'expected_return_at' => $borrowRequest->return_date,
                'return_condition' => 'pending',
            ]);
        }

        // Dispatch should succeed and not change stock; instances remain allocated
        $dispatchRes = $this->postJson('/admin/borrow-requests/' . $borrowRequest->id . '/dispatch');
        $dispatchRes->assertStatus(200)->assertJson(['message' => 'Items dispatched successfully.']);

        $borrowRequest->refresh();
        $item->refresh();
        $this->assertEquals('approved', $borrowRequest->status);
        $this->assertEquals('dispatched', $borrowRequest->delivery_status);
        $this->assertEquals(5, $item->available_qty);

        $rows = BorrowItemInstance::where('borrow_request_id', $borrowRequest->id)->get();
        $this->assertCount(2, $rows);
        foreach ($rows as $row) {
            $this->assertEquals('allocated', $row->instance->status);
        }
    }
}
